<?php

namespace Emoncms\Config;

class RedisConfig
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $auth;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var bool
     */
    private $isRedisEnabled;

    /**
     * @param bool $isRedisEnabled
     * @param string $host
     * @param int $port
     * @param string $auth
     * @param string $prefix
     */
    public function __construct($isRedisEnabled, $host, $port, $auth, $prefix)
    {
        $this->isRedisEnabled = $isRedisEnabled;
        $this->host = $host;
        $this->port = $port;
        $this->auth = $auth;
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * @return bool
     */
    public function hasAuth()
    {
        return !empty($this->auth);
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return bool
     */
    public function hasPrefix()
    {
        return !empty($this->prefix);
    }

    /**
     * @return bool
     */
    public function hasValidConfig()
    {
        return $this->isRedisEnabled && !empty($this->host) && !empty($this->port);
    }
}