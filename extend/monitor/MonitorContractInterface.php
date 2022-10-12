<?php

namespace monitor;

use block_chain\BlockChainCoroutineService;
use block_chain\BlockChainTool;
use Exception;

/**
 * 监听合约地址抽象类
 *
 * Class MonitorContractInterface
 * @package app\admin\service
 */
abstract class MonitorContractInterface extends BaseMonitorInterface
{
    /**
     * MonitorContractInterface constructor.
     * @param $contractFunction
     * @param $transactionInfo
     * @param $pdoPool
     * @throws Exception
     */
    public function __construct($contractFunction, $transactionInfo, $pdoPool)
    {
        $this->chainId                    = config('block_chain.chain_id');
        $this->contractFunction           = $contractFunction;
        $this->transactionInfo            = $transactionInfo;
        $this->pdoPool                    = $pdoPool;
        $this->blockChainCoroutineService = new BlockChainCoroutineService();

        // 获取交易回执，包含交易是否成功
        $this->transactionReceipt = $this->blockChainCoroutineService->getTransactionReceiptCo($transactionInfo['hash']);

        if (empty($this->transactionReceipt)) {
            throw new Exception("交易{$transactionInfo['hash']}的收据不存在{$this->transactionReceipt}");
        }

        $this->status = BlockChainTool::hexToNumber($this->transactionReceipt['status']);
    }
}