<?php
/**
 * This file is part of Swoole.
 *
 * @link     https://www.swoole.com
 * @contact  team@swoole.com
 * @license  https://github.com/swoole/library/blob/master/LICENSE
 */

declare(strict_types = 1);

namespace swoole_library\database;

use RuntimeException;
use Swoole\ConnectionPool;
use think\db\ConnectionInterface;
use Throwable;

class PDOPool extends ConnectionPool
{
    /**
     * PDOPool constructor.
     * @param int $size
     */
    public function __construct(int $size = self::DEFAULT_SIZE)
    {
        parent::__construct(function () {
        }, $size);
    }

    /**
     * @param float|int $timeout
     * @return ConnectionInterface
     * @throws Throwable
     */
    public function get(float $timeout = -1)
    {
        if ($this->pool === null) {
            throw new RuntimeException('Pool has been closed');
        }
        if ($this->pool->isEmpty() && $this->num < $this->size) {
            $this->make();
        }
        return $this->pool->pop($timeout);
    }

    /**
     * @throws Throwable
     */
    protected function make(): void
    {
        $this->num++;
        try {
            $connection = app()->db->connect(null, true);
        } catch (Throwable $throwable) {
            $this->num--;
            throw $throwable;
        }
        $this->put($connection);
    }
}
