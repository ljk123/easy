<?php


namespace easy\redis\swoole;


use easy\exception\AttrNotFoundException;
use easy\exception\RedisException;
use easy\redis\Interfaces;

/**
 * @method  get
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
class Redis implements Interfaces
{
    // todo 连接池
    private $handler;
    private $config;
    protected $connected=false;
    protected $connect_error='';
    protected $connect_errno=0;
    public function connect(array $config = [])
    {
        $this->config=$config;
        $this->handler = new \Swoole\Coroutine\Redis();
        $this->handler->connect($this->config['host'], (int) $this->config['port']);
        if($this->config['password']) {
            $this->handler->auth($this->config['password']);
        }
        if($this->config['db']) {
            $this->handler->select($this->config['db']);
        }
        $this->connected=$this->handler->connected;
        if(!$this->connected)
        {
            $this->connect_errno=$this->handler->errCode;
            $this->connect_error=$this->handler->errMsg;
            return false;
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws RedisException
     */
    public function __call($name, $arguments)
    {
        if(empty($this->connected))
        {
            throw new RedisException('redis has no connected',$this->config);
        }
        try {
            return call_user_func_array([$this->handler,$name],$arguments);
        }
        catch (\RedisException $e)
        {
            throw new RedisException($e->getMessage(),$this->config);
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
        ]))
        {
            throw new AttrNotFoundException('attr not found',$name);
        }
        return $this->$name;
    }
}