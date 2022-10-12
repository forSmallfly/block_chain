<?php
namespace chain;

use swoole_library\database\PDOPool;
use swoole_library\database\RedisPool;
use Throwable;

abstract class BaseServer
{
    /**
     * 进程锁key
     *
     * @var string
     */
    protected $lockKey;

    /**
     * 数据库连接池
     *
     * @var PDOPool
     */
    protected $pdoPool;

    /**
     * redis连接池
     *
     * @var RedisPool
     */
    protected $redisPool;

    /**
     * BlockSyncServer constructor.
     */
    public function __construct()
    {
        $this->pdoPool   = new PDOPool();
        $this->redisPool = new RedisPool();
    }

    /**
     * 设置进程锁key
     *
     * @return mixed
     */
    abstract protected function setLockKey();

    /**
     * 进程加锁
     *
     * @return bool
     * @throws Throwable
     */
    public function addLock()
    {
        $redis  = $this->redisPool->get();
        $key    = $this->lockKey;
        $result = $redis->setnx($key, date('Y-m-d H:i:s'));

        $this->redisPool->put($redis);

        return $result;
    }

    /**
     * 进程解锁
     *
     * @return bool
     * @throws Throwable
     */
    public function unLock()
    {
        $redis  = $this->redisPool->get();
        $key    = $this->lockKey;
        $lua    = <<<EOT
                    if redis.call("get",KEYS[1]) ~= nil then
                        return redis.call("del",KEYS[1])
                    else
                        return 0
                    end 
EOT;
        $result = $redis->eval($lua, [$key], 1);

        $this->redisPool->put($redis);

        return $result;
    }

    /**
     * 获取钱包信息
     *
     * @param string $type
     * @param array  $fromAddressList
     * @param array  $fromAddressKeyList
     * @param int    $chainId
     * @param array  $failAddress
     * @return array
     * @throws Throwable
     */
    public function getWalletInfo(string $type, array $fromAddressList, array $fromAddressKeyList, int $chainId, array $failAddress = [])
    {
        $redis            = $this->redisPool->get();
        $count            = count($fromAddressList);
        $redisKey         = $type . '_wallet_index';
        $alreadyUsedIndex = $redis->get($redisKey);

        if (($alreadyUsedIndex >= $count - 1) || $alreadyUsedIndex === false) {
            $redis->set($redisKey, 0);
            $walletInfo = [
                'from_address'     => $fromAddressList[0],
                'from_address_key' => $fromAddressKeyList[0]
            ];
        } else {
            $willUseIndex = $alreadyUsedIndex + 1;
            $redis->set($redisKey, $willUseIndex);
            $walletInfo = [
                'from_address'     => $fromAddressList[$willUseIndex],
                'from_address_key' => $fromAddressKeyList[$willUseIndex]
            ];
        }

        $this->redisPool->put($redis);

        if (!in_array($walletInfo['from_address'], $failAddress)) {
            return $walletInfo;
        } else {
            return $this->getWalletInfo($type, $fromAddressList, $fromAddressKeyList, $chainId, $failAddress);
        }
    }
}