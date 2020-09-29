<?php


namespace easy\swoole\pool;


use easy\Exception;
use Swoole\Coroutine\Channel;

class Pool
{
    private static $instances;
    /**@var Channel $pool */
    private $pool=null;
    private $pushed_size = 0;
    public static function getInstance($config)
    {
        $key=$config['name'];
        if (empty(self::$instances[$key])) {
            self::$instances[$key] = new self($config);
        }
        return self::$instances[$key];
    }
    private function __construct($config)
    {
        $this->pool = new Channel($config['max_size']);
    }
    private function __clone()
    {
    }
    public function push($link){
        $this->pool->push($link);
        $this->pushed_size++;
    }

    /**
     * @return int
     */
    public function pushed(){
        return $this->pushed_size;
    }
    public function length(){
        return $this->pool->length();
    }
    public function get()
    {
        if(is_null($this->pool))
        {
            throw new Exception("call init first");
        }
        $link = $this->pool->pop();
        if (false === $link) {
            throw new Exception("Pop ".get_class($link)." timeout");
        }
        defer(function () use ($link) { //释放
            $this->pool->push($link);
        });
        return $link;
    }
    //创建定时器保持链接
}