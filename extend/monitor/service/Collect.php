<?php
namespace monitor\service;

use block_chain\BlockChainTool;
use monitor\MonitorWalletInterface;
use Throwable;
use Web3\Utils;

class Collect extends MonitorWalletInterface
{
    /**
     * 收款交易
     *
     * @return bool
     * @throws Throwable
     */
    public function transfer()
    {
        // 调用数据库连接池
        $connection = $this->pdoPool->get();

        // 检测交易是否已处理
        if ($this->transactionIsProcessed($connection)) {
            // 交易已经处理，提前把连接放回连接池，并返回处理结果
            $this->pdoPool->put($connection);
            return true;
        }

        $results = [];
        // 开启事务
        $connection->startTrans();
        try {
            // 添加交易过滤日志
            $results[] = $this->insertFilterTransactionLog($connection);
            // 交易成功时处理业务逻辑
            if ($this->status === 1) {
                /******************************************从这里开始业务逻辑代码*****************************************/
                $data      = [
                    'tx_hash'      => $this->transactionInfo['hash'],
                    'user_address' => Utils::toChecksumAddress($this->transactionInfo['from']),
                    'amount'       => BlockChainTool::hexToEther($this->transactionInfo['value']),
                ];
                $results[] = $connection->name('transfer')->insert($data);
                /******************************************从这里结束业务逻辑代码*****************************************/
                foreach ($results as $result) {
                    // 数据库操作失败时，回滚事务，并提前把连接放回连接池，处理结果返回失败
                    if (empty($result)) {
                        $connection->rollback();
                        $this->pdoPool->put($connection);
                        return false;
                    }
                }

                // 数据库操作成功时，提交事务，并提前把连接放回连接池
                $connection->commit();
                $this->pdoPool->put($connection);
            }
        } catch (Throwable $throwable) {
            // 出现异常时，回滚事务，并向上级抛出异常
            $connection->rollback();
            // 最后把连接放回连接池
            $this->pdoPool->put($connection);

            throw new \Exception($throwable->getMessage());
        }

        // 返回处理结果
        return true;
    }
}