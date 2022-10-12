<?php
namespace monitor\service;

use block_chain\BlockChainTool;
use monitor\MonitorContractInterface;
use Throwable;
use Web3\Utils;

class Template extends MonitorContractInterface
{
    /**
     * 铸造Token
     *
     * @return bool
     * @throws Throwable
     */
    public function mint()
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
                $logs      = $this->transactionReceipt['logs'];
                $logInfo   = array_pop($logs);
                $logData   = $this->blockChainCoroutineService->decodeCustomByteCode('mint', Utils::stripZero($logInfo['data']));
                $data      = [
                    'tx_hash'      => $this->transactionInfo['hash'],
                    'user_address' => Utils::toChecksumAddress($logData[1]),
                    'amount'       => BlockChainTool::hexToEther($logData[2]),
                ];
                $results[] = $connection->name('mint_token_log')->insert($data);
                $results[] = $connection->name('mint_token_task')->where([
                    ['tx_hash', '=', $this->transactionInfo['hash']],
                    ['status', '=', '已发送']
                ])->update([
                    'status' => '处理成功'
                ]);
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