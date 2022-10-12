<?php
declare (strict_types = 1);

namespace app\command;

use block_chain\BlockChainService;
use block_chain\BlockChainTool;
use chain\TokenMintServer;
use Swoole\Coroutine\Barrier;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

/**
 * token自动铸造类
 *
 * @package app\command
 */
class AutoTokenMint extends Command
{
    /**
     * @var TokenMintServer
     */
    private $tokenMintServer;

    /**
     * 配置脚本
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('auto_mint')
             ->setDescription('自动铸造');
    }

    /**
     * 执行脚本
     *
     * @param Input  $input
     * @param Output $output
     * @return int|void|null
     */
    protected function execute(Input $input, Output $output)
    {
        ini_set("memory_limit", "1024M");
        run(function () use ($output) {
            // 记录初始化时间
            $initTime = microtime(true);
            try {
                // 实例化token铸造类
                $this->tokenMintServer = new TokenMintServer();

                $tokenMintServer = $this->tokenMintServer;

                // 同步进程 加锁
                $result = $tokenMintServer->addLock();
                if (!$result) {
                    $output->writeln('token铸造正在进行中...');
                    return;
                }

                // 获取铸造任务列表
                $taskList = $tokenMintServer->getMintTaskList();
                if (empty($taskList)) {
                    $output->writeln('暂无待处理任务，耗时：' . (microtime(true) - $initTime) . '秒');
                    // 退出之前先解锁
                    $tokenMintServer->unLock();
                    return;
                }

                $nonceList           = [];// nonce集合
                $failAddress         = [];// 获取nonce失败地址集合
                $tokenMinterRoleList = config('block_chain.token_minter_role_list');
                $blockChainServer    = new BlockChainService();

                // 使用协程屏障
                $barrier = new Barrier();
                foreach ($tokenMinterRoleList as $address => $addressKey) {
                    go(function () use ($blockChainServer, $address, $barrier, &$nonceList, &$failAddress, $output) {
                        try {
                            // 批量获取每个账号的nonce
                            $nonceList[$blockChainServer->getChainId() . '_' . $address] = BlockChainTool::hexToNumber($blockChainServer->getAddressTransactionNonce($address));
                        } catch (\Throwable $throwable) {
                            $failAddress[] = $address;
                            $output->writeln('获取nonce出错');
                        }
                    });
                }
                Barrier::wait($barrier);

                // nonce检测
                if (empty($nonceList)) {
                    $output->writeln('无可用nonce');
                    // 退出之前先解锁
                    $tokenMintServer->unLock();
                    return;
                }

                $retryTimes = explode(',', config('block_chain.retry_times'));
                foreach ($taskList as $taskInfo) {
                    // 设置任务状态为处理中
                    $result = $tokenMintServer->setTaskToProcessing($taskInfo['id']);
                    // 更新数量必须大于 0，不为0则有可能被执行了
                    if ($result <= 0) {
                        continue;
                    }

                    $fromAddressList    = array_keys($tokenMinterRoleList);
                    $fromAddressKeyList = array_values($tokenMinterRoleList);
                    $toAddress          = array_keys(config('block_chain.need_listen_contract_list'))[0];

                    // 获取钱包信息
                    $walletInfo     = $tokenMintServer->getWalletInfo('auto_token_mint', $fromAddressList, $fromAddressKeyList, $blockChainServer->getChainId(), $failAddress);
                    $fromAddress    = $walletInfo['from_address'];
                    $fromAddressKey = $walletInfo['from_address_key'];

                    $nonce  = BlockChainTool::numberToHex($nonceList[$blockChainServer->getChainId() . '_' . $fromAddress]);
                    $params = [
                        $taskInfo['user_address'],
                        BlockChainTool::numberToHex(BlockChainTool::etherToWei($taskInfo['amount']))
                    ];

                    go(function () use ($blockChainServer, $params, $toAddress, $fromAddress, $fromAddressKey, $taskInfo, $nonce, $retryTimes, $output) {
                        try {
                            $hash = $blockChainServer->upLink('mint', $params, $toAddress, $fromAddress, $fromAddressKey, '0x0', $nonce, $blockChainServer->getChainId());
                            if (isset($hash['status'])) {
                                // 计算下一次重试时间
                                $nextRetryTime = date('Y-m-d H:i:s', time() + $retryTimes[$taskInfo['retry_count']]);
                                // 设置任务状态为失败
                                $this->tokenMintServer->setTaskToFail($taskInfo['id'], $nextRetryTime, $hash['msg']);
                            } else {
                                // 设置任务状态为已发送
                                $this->tokenMintServer->setTaskToSent($taskInfo['id'], $hash);
                            }
                        } catch (\Throwable $throwable) {
                            $output->writeln('上链失败：' . $throwable->getMessage());
                        }
                    });

                    $nonceList[$blockChainServer->getChainId() . '_' . $fromAddress]++;
                }

                $output->writeln('交易完成，耗时：' . (microtime(true) - $initTime) . '秒');
            } catch (\Throwable $throwable) {
                $output->writeln('服务异常：' . $throwable->getMessage());
            }

            // 脚本执行完解锁
            $this->tokenMintServer->unLock();
        });
    }
}
