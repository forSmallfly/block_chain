<?php
namespace chain;

use think\db\exception\DbException;
use Throwable;

/**
 * token铸造类
 * @package chain
 */
class TokenMintServer extends BaseServer
{
    /**
     * 设置进程锁key
     *
     * @return mixed|void
     */
    protected function setLockKey()
    {
        $this->lockKey = config('block_chain.chain_id') . '_token_mint';
    }

    /**
     * 获取铸造任务列表
     *
     * @return array
     */
    public function getMintTaskList()
    {
        try {
            $db = $this->pdoPool->get();

            $lastBlockNumber = $db->name('mint_token_task')->where([
                ['next_retry_time', '<=', date('Y-m-d H:i:s')],
                ['retry_count', '<', config('block_chain.retry_count')],
                ['status', 'in', ['待处理', '处理失败']]
            ])->limit(100)->select()->toArray();

            $this->pdoPool->put($db);

            return $lastBlockNumber;
        } catch (Throwable $throwable) {
            return [];
        }
    }

    /**
     * 设置任务状态为处理中
     *
     * @param int $taskId
     * @return int
     * @throws DbException
     * @throws Throwable
     */
    public function setTaskToProcessing(int $taskId)
    {
        $db = $this->pdoPool->get();

        $result = $db->name('mint_token_task')->where([
            ['id', '=', $taskId],
            ['status', 'in', ['待处理', '处理失败']]
        ])->update(['status' => '处理中']);

        $this->pdoPool->put($db);

        return $result;
    }

    /**
     * 设置任务状态为失败
     *
     * @param int    $taskId
     * @param string $nextRetryTime
     * @param string $remark
     * @return int
     * @throws Throwable
     */
    public function setTaskToFail(int $taskId, string $nextRetryTime, string $remark)
    {
        $db = $this->pdoPool->get();

        $result = $db->name('mint_token_task')->where([
            ['id', '=', $taskId],
            ['status', '=', '处理中']
        ])->inc('retry_count')->update([
            'status'          => '处理失败',
            'next_retry_time' => $nextRetryTime,
            'remark'          => $remark
        ]);

        $this->pdoPool->put($db);

        return $result;
    }

    /**
     * 设置任务状态为已发送
     *
     * @param int    $taskId
     * @param string $hash
     * @return int
     * @throws Throwable
     */
    public function setTaskToSent(int $taskId, string $hash)
    {
        $db = $this->pdoPool->get();

        $result = $db->name('mint_token_task')->where([
            ['id', '=', $taskId],
            ['status', '=', '处理中']
        ])->inc('retry_count')->update([
            'tx_hash' => $hash,
            'status'  => '已发送'
        ]);

        $this->pdoPool->put($db);

        return $result;
    }
}