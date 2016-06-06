<?php

namespace Emoncms\Redis;

use Emoncms\Config\RedisConfig;
use Exception;

class Redis
{
    /**
     * @var RedisConfig
     */
    private $config;

    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @param RedisConfig $config
     */
    public function __construct(RedisConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function isRedisEnabled()
    {
        return $this->config->hasValidConfig();
    }

    /**
     * Get Redis connection.
     *
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * Close Redis connection.
     */
    public function close()
    {
        if ($this->isConnected()) {
            $this->redis->close();
        }
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->redis !== null;
    }

    /**
     * @return \Redis
     * @throws Exception
     */
    public function connect()
    {
        $this->redis = new \Redis();
        $connected = $this->redis->connect($this->config->getHost(), $this->config->getPort());

        if (!$connected) {
            throw new Exception(sprintf('Cannot connect to redis at %s:%d, it may be that redis-server is not installed or started see readme for redis installation',
                $this->config->getHost(), $this->config->getPort()));
        }

        if ($this->config->hasPrefix()) {
            $this->redis->setOption(\Redis::OPT_PREFIX, $this->config->getPrefix());
        }

        if ($this->config->hasAuth()) {
            if (!$this->redis->auth($this->config->getAuth())) {
                throw new Exception(sprintf('Cannot connect to redis at %s:%d, authentication failed',
                    $this->config->getHost(), $this->config->getPort()));
            }
        }
    }

}