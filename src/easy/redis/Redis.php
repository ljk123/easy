<?php


namespace easy\redis;


use easy\exception\AttrNotFoundException;
use easy\exception\RedisException;

/**
 * @method string get
 * @method  set(string $key, string $value)
 * @method  setex
 * @method  setnx
 * @method  delete
 * @method  exists
 * @method  incr
 * @method  getMultiple
 * @method  lpush
 * @method  rpush
 * @method  lpop
 * @method  lsize
 * @method  len
 * @method  lget
 * @method  lset
 * @method  lgetrange
 * @method  lremove
 * @method  sadd
 * @method  sremove
 * @method  smove
 * @method  scontains
 * @method  ssize
 * @method  spop
 * @method  sinter
 * @method  sinterstore
 * @method  sunion
 * @method  sunionstore
 * @method  sdiff
 * @method  sdiffstore
 * @method  smembers
 * @method  sgetmembers
 */
class Redis implements Interfaces, \easy\swoole\pool\Interfaces
{
    /**@var \Redis $handler */
    protected $handler;

    public function __construct()
    {
    }

    protected $config = [];//配置
    protected $connected = false;
    protected $connect_error = '';
    protected $connect_errno = 0;

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws RedisException
     */
    public function __call($name, $arguments)
    {
        if (empty($this->connected)) {
            throw new RedisException('redis has no connected', $this->config);
        }
        try {
            $this->last_use_time = time();
            return call_user_func_array([$this->handler, $name], $arguments);
        } catch (\RedisException $e) {
            throw new RedisException($e->getMessage(), $this->config);
        }
    }

    /**
     * @param $name
     * @return mixed
     * @throws AttrNotFoundException
     */
    public function __get($name)
    {
        if (!in_array($name, [
            'config',
            'connected',
            'connect_error',
            'connect_errno',
            'error',
            'errno',
        ])) {
            throw new AttrNotFoundException('attr not found', $name);
        }
        return $this->$name;
    }

    public function connect(array $config = [])
    {
        $this->config = $config;
        $this->handler = new \Redis();
        $this->handler->connect($this->config['host'], (int)$this->config['port'], (int)$this->config['timeout']);
        if ($this->config['password']) {
            $this->handler->auth($this->config['password']);
        }
        if ($this->config['db']) {
            $this->handler->select($this->config['db']);
        }
        try {
            if ($this->handler->ping()) {
                $this->connected = true;
            } else {
                $this->handler = null;
            }
        } catch (\RedisException $e) {
            $this->connect_errno = $e->getCode();
            $this->connect_error = $e->getMessage();
            return false;
        }
    }

    public function ping()
    {
        return $this->handler->ping();
    }

    protected $last_use_time = 0;

    public function lastUseTime()
    {
        return $this->last_use_time;
    }
}