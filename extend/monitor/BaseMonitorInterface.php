<?php
namespace monitor;

use block_chain\BlockChainCoroutineService;
use block_chain\BlockChainTool;
use swoole_library\database\PDOPool;
use think\db\ConnectionInterface;

abstract class BaseMonitorInterface
{
    /**
     * 链标识
     *
     * @var mixed
     */
    protected $chainId;

    /**
     *
     *
     * @var array
     */
    protected $contractFunction;

    /**
     * 交易信息
     *
     * @var array
     */
    protected $transactionInfo;

    /**
     * 数据库连接池
     *
     * @var PDOPool
     */
    protected $pdoPool;

    /**
     * 交易回执
     *
     * @var mixed
     */
    protected $transactionReceipt;

    /**
     * 交易状态
     *
     * @var int
     */
    protected $status;

    /**
     * 交易监听过滤表
     *
     * @var string
     */
    protected $filter_transaction_table = 'filter_transaction';

    /**
     * @var BlockChainCoroutineService
     */
    protected $blockChainCoroutineService;

    /**
     * 检测交易是否已处理
     *
     * @param $connection ConnectionInterface
     * @return bool
     */
    protected function transactionIsProcessed($connection)
    {
        $id = $connection->name($this->filter_transaction_table)->where([
            ['block_number', '=', BlockChainTool::hexToNumber($this->transactionInfo['blockNumber'])],
            ['tx_hash', '=', $this->transactionInfo['hash']],
        ])->value('id');

        return !empty($id);
    }

    /**
     * 添加交易过滤日志
     *
     * @param $connection ConnectionInterface
     * @return int|string
     */
    protected function insertFilterTransactionLog($connection)
    {
        $filterTransactionData = [
            'block_number' => BlockChainTool::hexToNumber($this->transactionInfo['blockNumber']),
            'tx_hash'      => $this->transactionInfo['hash'],
            'tx_index'     => BlockChainTool::hexToNumber($this->transactionInfo['transactionIndex']),
            'tx_value'     => BlockChainTool::hexToEther($this->transactionInfo['value']),
            'tx_status'    => $this->status,
        ];

        return $connection->name($this->filter_transaction_table)->insert($filterTransactionData);
    }
}