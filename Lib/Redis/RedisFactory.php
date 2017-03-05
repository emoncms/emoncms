<?php

namespace Emoncms\Redis;

use Emoncms\Config\RedisConfig;
use Exception;
use Redis;

class RedisFactory
{
    /**
     * @var RedisConfig
     */
    private $config;

    /**
     * @var Redis
     */
    private $redis;

    /**
     * @param RedisConfig $config
     * @throws Exception
     */
    public function __construct(RedisConfig $config)
    {
        $this->config = $config;

        if ($this->config->hasValidConfig()) {
            $this->initialize();
        }
    }

    /**
     * Get Redis connection. Return false if connection not connected.
     *
     * @return Redis|false
     */
    public function getRedis()
    {
        if (!$this->isInitialized()) {
            return false;
        }

        return $this->redis;
    }

    /**
     * @return bool
     */
    private function isInitialized()
    {
        return $this->redis !== null;
    }

    /**
     * @throws Exception
     */
    private function initialize()
    {
        $this->redis = new Redis();

        if ($this->redis->connect($this->config->getHost(), $this->config->getPort()) === false) {
            throw new Exception(sprintf('Cannot connect to redis at %s:%d, it may be that redis-server is not installed or started see readme for redis installation',
                $this->config->getHost(), $this->config->getPort()));
        }

        if ($this->config->hasPrefix()) {
            $this->redis->setOption(Redis::OPT_PREFIX, $this->config->getPrefix());
        }

        if ($this->config->hasAuth()) {
            if (!$this->redis->auth($this->config->getAuth())) {
                throw new Exception(sprintf('Cannot connect to redis at %s:%d, authentication failed',
                    $this->config->getHost(), $this->config->getPort()));
            }
        }
    }

}