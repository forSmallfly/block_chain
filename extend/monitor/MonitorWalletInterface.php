<?php

namespace monitor;

/**
 * 监听合约地址抽象类
 *
 * Class MonitorContractInterface
 * @package app\admin\service
 */
abstract class MonitorWalletInterface extends BaseMonitorInterface
{
    /**
     * MonitorWalletInterface constructor.
     * @param $transactionInfo
     * @param $pdoPool
     */
    public function __construct($transactionInfo, $pdoPool)
    {
        $this->chainId         = config('block_chain.chain_id');
        $this->transactionInfo = $transactionInfo;
        $this->pdoPool         = $pdoPool;

        $this->status = 1;
    }

    abstract protected function transfer();
}