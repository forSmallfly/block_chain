<?php
declare (strict_types = 1);

namespace app\command;

use chain\BlockSyncServer;
use Exception;
use Swoole\Coroutine\Barrier;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use function \Swoole\Coroutine\run;
use function \Swoole\Coroutine\go;

/**
 * 区块同步类
 *
 * @package app\command
 */
class BlockSync extends Command
{
    /**
     * 区块同步服务类
     *
     * @var BlockSyncServer
     */
    private $blockSyncServer;

    /**
     * 配置脚本
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('block_sync')
             ->setDescription('区块同步');
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
                // 实例化区块同步服务类
                $this->blockSyncServer = new BlockSyncServer();

                $blockSyncServer = $this->blockSyncServer;

                // 同步进程 加锁
                $result = $blockSyncServer->addLock();
                if (!$result) {
                    $output->writeln('同步正在进行中...');
                    return;
                }

                // 获取上次监听到的区块号及当前最新区块号
                [$lastBlockNumber, $nowBlockNumber] = $this->getBlockNumberList($blockSyncServer);

                // 计算监听区块总数
                if (is_numeric($lastBlockNumber)) {
                    $diff            = $nowBlockNumber - $lastBlockNumber;
                    $lastBlockNumber = $lastBlockNumber + 1;// 这里加一，从上次监听到的区块号下一个区块开始同步
                } else {
                    $diff            = 1;
                    $lastBlockNumber = $nowBlockNumber;
                }

                // 检测当前需要处理的区块数量
                if ($diff <= 0) {
                    $output->writeln("暂无可监听的区块");
                    // 退出之前先解锁
                    $blockSyncServer->unLock();
                    return;
                }

                $listenBlockNumberMax = config('block_chain.listen_block_number_max');
                // 检测同步区块数量是否超过脚本处理最大块数
                if ($diff > $listenBlockNumberMax) {
                    $diff           = $listenBlockNumberMax;
                    $nowBlockNumber = $lastBlockNumber + $listenBlockNumberMax - 1;
                }

                // 生成需要同步的区块号集合
                $blockNumberList = range($lastBlockNumber, $nowBlockNumber);

                // 切割区块集合
                $blockNumberListMap = array_chunk($blockNumberList, config('block_chain.listen_block_number'));

                foreach ($blockNumberListMap as $blockSection) {
                    $startTime     = microtime(true);
                    $blockInfoList = [];
                    // 使用协程屏障
                    $barrier = new Barrier();
                    foreach ($blockSection as $blockNumber) {
                        go(function () use ($blockSyncServer, $barrier, $blockNumber, &$blockInfoList) {
                            $blockInfoList[$blockNumber] = [];
                            try {
                                // 使用协程获取该区块的交易信息，并设置获取次数上限，达到上限后停止获取，设置获取结果为空数组
                                $blockInfo = $blockSyncServer->getBlockChainCoroutineService()->getBlockInfoCo($blockNumber);
                                if (!empty($blockInfo)) {
                                    $blockInfoList[$blockNumber] = $blockInfo;
                                }
                            } catch (\Throwable $throwable) {
                            }
                        });
                    }
                    Barrier::wait($barrier);

                    // 按区块编号从小到大排序
                    ksort($blockInfoList);
                    foreach ($blockInfoList as $blockNumber => $blockInfo) {
                        if (empty($blockInfo)) {
                            // 区块没有取成功，抛出异常，下次脚本执行续上
                            throw new Exception("获取到区块{$blockNumber}信息失败");
                        }
                        // 过滤区块交易
                        $blockSyncServer->filterTransaction($blockInfo);
                    }
                    // 命令行日志输出
                    $this->output->writeln("处理区块{$blockSection[0]}至" . end($blockSection) . " 结束，共" . count($blockSection) . "个区块，交易处理耗时：" . round(microtime(true) - $startTime, 8) . "秒");

                    // 释放已处理的块信息
                    unset($blockInfoList);
                    $sleepTime = config('block_chain.listen_block_sleep');
                    // 延时操作
                    if ($sleepTime > 0) {
                        usleep($sleepTime);
                    }
                }

                $usedTime = round(microtime(true) - $initTime, 8);
                $output->writeln("本次监听操作已完成，共同步{$diff}个区块，共耗时{$usedTime}秒");
            } catch (\Throwable $throwable) {
                // 本次监听操作出现异常，添加日志记录
                $errMsgArray = [
                    '文件'   => $throwable->getFile(),
                    '行数'   => $throwable->getLine(),
                    '错误信息' => $throwable->getMessage()
                ];

                $output->writeln('同步区块时出错：' . var_export($errMsgArray, true));
            }

            // 脚本执行完解锁
            $this->blockSyncServer->unLock();
        });
    }

    /**
     * 获取上次监听到的区块号及当前最新区块号
     *
     * @param $blockSyncServer BlockSyncServer
     * @return array
     * @throws \Swoole\Exception
     */
    public function getBlockNumberList($blockSyncServer)
    {
        // 使用协程屏障
        $barrier = new Barrier();
        go(function () use ($blockSyncServer, $barrier, &$lastBlockNumber) {
            try {
                // 获取上次监听到的区块号
                $lastBlockNumber = $blockSyncServer->getLastBlockNumber();
            } catch (\Throwable $throwable) {
                $lastBlockNumber = [
                    'error' => $throwable
                ];
            }
        });
        go(function () use ($blockSyncServer, $barrier, &$nowBlockNumber) {
            try {
                // 获取当前最新区块号
                $nowBlockNumber = $blockSyncServer->getNowBlockNumber();
            } catch (\Throwable $throwable) {
                $nowBlockNumber = [
                    'error' => $throwable
                ];
            }
        });
        Barrier::wait($barrier);

        $results = [$lastBlockNumber, $nowBlockNumber];
        foreach ($results as $result) {
            if (is_array($result)) {
                throw $result['error'];
            }
        }

        return [$lastBlockNumber, $nowBlockNumber];
    }
}