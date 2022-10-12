<?php
namespace block_chain;

use Swoole\Coroutine;
use think\console\Output;

/**
 * 区块链服务类（协程版）
 * @package block_chain
 */
class BlockChainCoroutineService extends BlockChainService
{
    /**
     * 获取区块信息，该函数只能在协程内调用
     *
     * @param int $blockNumber
     * @param int $currTimes
     * @return bool|mixed
     */
    public function getBlockInfoCo(int $blockNumber, int $currTimes = 0)
    {
        $tryTimes = config('block_chain.listen_block_try_times');

        try {
            $blockInfo = $this->getBlockInfo($blockNumber);
        } catch (\Throwable $throwable) {
            $this->consoleWrite("没有获取到区块{$blockNumber}");
            $blockInfo = false;
        }

        if (!$blockInfo) {
            if ($tryTimes > 0 && $tryTimes > $currTimes) {
                $sleep = $this->getTrySleepTime($currTimes);
                if ($sleep >= 0.001) {
                    Coroutine::sleep($sleep);
                }

                return $this->getBlockInfoCo($blockNumber, ++$currTimes);
            }

            return false;
        }

        return $blockInfo;
    }

    /**
     * 获取交易信息，该函数只能在协程内调用
     *
     * @param string $txHash
     * @param int    $currTimes
     * @return bool|mixed
     */
    public function getTransactionReceiptCo(string $txHash, int $currTimes = 0)
    {
        $tryTimes = config('block_chain.listen_receipt_try_times');

        try {
            $info = $this->getTransactionReceipt($txHash);
        } catch (\Throwable $throwable) {
            $this->consoleWrite("没有获取到交易{$txHash}");
            $info = false;
        }

        if (!$info) {
            if ($tryTimes > 0 && $tryTimes > $currTimes) {
                $sleep = $this->getTrysleepTime($currTimes);
                if ($sleep >= 0.001) {
                    Coroutine::sleep($sleep);
                }

                return $this->getTransactionReceiptCo($txHash, ++$currTimes);
            }

            return false;
        }

        return $info;
    }

    /**
     * 获取历史日志列表，该函数只能在协程内调用
     *
     * @param int   $fromBlock
     * @param int   $toBlock
     * @param array $topics
     * @param array $address
     * @param int   $currTimes
     * @return bool|mixed
     */
    public function getPastEventsCo(int $fromBlock, int $toBlock, array $topics, array $address, $currTimes = 0)
    {
        $tryTimes = config('block_chain.listen_receipt_try_times');

        try {
            $info = $this->getPastEvents($fromBlock, $toBlock, $topics, $address);
        } catch (\Throwable $throwable) {
            $this->consoleWrite("没有获取到历史日志列表，区块{$fromBlock}~{$toBlock}");
            $info = false;
        }

        if (!$info) {
            if ($tryTimes > 0 && $tryTimes > $currTimes) {
                $sleep = $this->getTrysleepTime($currTimes);
                if ($sleep >= 0.001) {
                    Coroutine::sleep($sleep);
                }

                return $this->getPastEventsCo($fromBlock, $toBlock, $topics, $address, ++$currTimes);
            }

            return false;
        }

        return $info;
    }

    /**
     * 获取重试间隔时间配置
     *
     * @param int $times
     * @return float|mixed|string
     */
    private function getTrySleepTime($times = 0)
    {
        $default = 0.1;
        $config  = explode(',', config('block_chain.listen_block_try_sleep_time'));
        if ($config) {
            return $config[$times] ?? $default;
        }

        return $default;
    }

    /**
     * 日志输出
     *
     * @param $messages
     */
    private function consoleWrite($messages)
    {
        (new Output())->writeln($messages);
    }
}