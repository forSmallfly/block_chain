<?php
namespace chain;

use block_chain\BlockChainCoroutineService;
use block_chain\BlockChainTool;
use Exception;
use Swoole\Coroutine\Channel;
use Throwable;
use Web3\Utils;
use function Swoole\Coroutine\go;

/**
 * 区块同步服务类
 * @package chain
 */
class BlockSyncServer extends BaseServer
{
    /**
     * 区块链服务类（协程版）
     *
     * @var BlockChainCoroutineService
     */
    protected $blockChainCoroutineService;

    /**
     * 需要监听的合约地址（全部转成小写）
     *
     * @var array
     */
    protected $needListenContractList;

    /**
     * 需要监听的钱包地址，主要用于监听转账（全部转成小写）
     *
     * @var array
     */
    protected $needListenWalletList;

    /**
     * 获取上次监听到的区块号redis Key
     *
     * @var string
     */
    protected $lastBlockNumberKey = 'last_block_number';

    /**
     * BlockSyncServer constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->blockChainCoroutineService = new BlockChainCoroutineService();

        // 需要监听的合约地址（全部转成小写）
        $this->needListenContractList = array_map('mb_strtolower', config('block_chain.need_listen_contract_list'));
        // 需要监听的钱包地址，主要用于监听转账（全部转成小写）
        $this->needListenWalletList = array_map('mb_strtolower', config('block_chain.need_listen_wallet_list'));
    }

    /**
     * 设置进程锁key
     *
     * @return mixed|void
     */
    protected function setLockKey()
    {
        $this->lockKey = config('block_chain.chain_id') . '_block_sync';
    }

    /**
     * 获取上次监听到的区块号
     *
     * @return bool|mixed|string
     * @throws Throwable
     */
    public function getLastBlockNumber()
    {
        $redis           = $this->redisPool->get();
        $lastBlockNumber = $redis->get($this->lastBlockNumberKey);
        $this->redisPool->put($redis);

        // 当redis中还没有设置上次监听到的区块号时，读取上次过滤交易的最大区块号
        if (empty($lastBlockNumber)) {
            $db = $this->pdoPool->get();

            $lastBlockNumber = $db->name('filter_transaction')->max('block_number');
            $this->pdoPool->put($db);
        }

        // 当没有查询到，默认返回最新的区块号
        return $lastBlockNumber ?: 'latest';
    }

    /**
     * 获取当前最新区块号
     *
     * @return int
     * @throws Exception
     */
    public function getNowBlockNumber()
    {
        $blockChainCoroutineService = new BlockChainCoroutineService();

        $blockNumber = $blockChainCoroutineService->getBlockNumber();
        return BlockChainTool::hexToNumber($blockNumber);
    }

    /**
     * 获取区块链服务类（协程版）
     *
     * @return BlockChainCoroutineService
     */
    public function getBlockChainCoroutineService()
    {
        return $this->blockChainCoroutineService;
    }

    /**
     * 过滤区块交易
     *
     * @param array $blockInfo
     * @throws Throwable
     */
    public function filterTransaction(array $blockInfo)
    {
        // 获取交易列表
        $transactions     = $blockInfo['transactions'] ?? [];
        $transactionCount = count($transactions);
        $filterCount      = 0;
        if (!empty($transactions)) {
            $channel = new Channel($transactionCount);
            foreach ($transactions as $transactionInfo) {
                go(function () use ($transactionInfo, &$filterCount, $channel) {
                    try {
                        $result    = true;
                        $toAddress = mb_strtolower($transactionInfo['to']);
                        // 需要监听的合约地址
                        if (in_array($toAddress, array_keys($this->needListenContractList))) {
                            $transactionInput = Utils::stripZero($transactionInfo['input']);
                            // 解码input
                            $contractFunction = $this->blockChainCoroutineService->decodeContractFunction($transactionInput);
                            $functionLength   = strpos($contractFunction['method'], '(');
                            if ($functionLength) {
                                $monitorClass = $this->needListenContractList[$toAddress];
                                if (!class_exists($monitorClass)) {
                                    throw new \Exception('监听合约类：' . $monitorClass . '不存在');
                                }

                                $functionName = substr($contractFunction['method'], 0, $functionLength);
                                if (method_exists($monitorClass, $functionName)) {
                                    $monitorObject = app()->make($monitorClass, [$contractFunction, $transactionInfo, $this->pdoPool], true);
                                    $result        = $monitorObject->$functionName();
                                    $filterCount++;
                                }
                            }
                        }

                        // 需要监听的钱包地址
                        if (in_array($toAddress, array_keys($this->needListenWalletList))) {
                            $monitorClass = $this->needListenWalletList[$toAddress];
                            if (!class_exists($monitorClass)) {
                                throw new \Exception('监听合约类：' . $monitorClass . '不存在');
                            }

                            $functionName = 'transfer';
                            if (method_exists($monitorClass, $functionName)) {
                                $monitorObject = app()->make($monitorClass, [$transactionInfo, $this->pdoPool], true);
                                $result        = $monitorObject->$functionName();
                                $filterCount++;
                            }
                        }

                        if ($result) {
                            $channel->push(['error' => false, 'hash' => $transactionInfo['hash'], 'err_msg' => '']);
                        } else {
                            $channel->push(['error' => true, 'hash' => $transactionInfo['hash'], 'err_msg' => '业务逻辑处理失败！']);
                        }
                    } catch (Throwable $throwable) {
                        $errMsg = '【文件 ' . $throwable->getFile() . '】【行数 ' . $throwable->getLine() . "】【错误信息 " . $throwable->getMessage() . '】';
                        $channel->push(['error' => true, 'hash' => $transactionInfo['hash'], 'err_msg' => $errMsg]);
                    }
                });
            }

            // 等待全部执行完后，检测是否有异常
            foreach ($transactions as $key => $tx) {
                $result = $channel->pop();
                if ($result['error']) {
                    throw new Exception(' hash:' . $result['hash'] . ";errMsg:" . $result['err_msg']);
                }
            }
        }

        // 区块过滤完成后设置监听区块缓存
        $redis       = $this->redisPool->get();
        $blockNumber = BlockChainTool::hexToNumber($blockInfo['number']);
        $redis->set($this->lastBlockNumberKey, $blockNumber);
        $this->redisPool->put($redis);
    }
}