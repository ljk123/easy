<?php


namespace easy\swoole\pool;


use easy\Exception;
use easy\exception\InvalidArgumentException;
use Swoole\Coroutine\Channel;
use Swoole\Timer;

class Pool
{
    private static $instances;
    /**@var Channel $pool */
    private $pool = null;
    private $pushed_size = 0;
    private $config;

    /**
     * @param $config
     * @return self
     */
    public static function getInstance($config)
    {
        $key = $config['name'];
        if (empty(self::$instances[$key])) {
            self::$instances[$key] = new self($config);
        }
        return self::$instances[$key];
    }

    private function __construct($config)
    {
        if (empty($config['timeout'])) {
            $config['timeout'] = 2;
        }
        $this->config = $config;
        $this->pool = new Channel($config['max_size']);
        Timer::tick(5 * 1000, function () {
            //定时器保持心跳
            /**@var Interfaces $link */
            if ($link = $this->pool->pop(0.01)) {
                if (!$link->ping()) {
                    //断开了就不返还了丢了
                    $this->pushed_size--;
                    $link = null;
                    return;
                }
                $this->pool->push($link);
            }
        });
        //释放空闲链接 10分钟一次
        Timer::tick(10 * 60 * 1000, function () use ($config) {
            if ($this->pool->length() < intval($config['max_size'] * 0.5)) {
                // 请求连接数还比较多，暂时不回收空闲连接
                return;
            }
            while (true) {
                if ($this->pool->length() <= $config['min_size']) {
                    break;
                }
                /** @var Interfaces $link */
                if ($link = $this->pool->pop(0.001)) {
                    $nowTime = time();
                    $lastUsedTime = $link->lastUseTime();

                    // 当前连接数大于最小的连接数，并且回收掉空闲的连接
                    if ($this->pushed_size > $config['min_size'] && ($nowTime - $lastUsedTime > $config['free_time'])) {
                        $link = null;
                        $this->pushed_size--;
                    } else {
                        $this->pool->push($link);
                    }
                }
            }
        });
    }

    private function __clone()
    {
    }

    /**
     * @param $callback
     * @param array $params
     * @throws InvalidArgumentException
     */
    public function create($callback, array $params = [])
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('callback must be callable');
        }
        for ($i = 0; $i < $this->config['min_size']; $i++) {
            $this->createOne($callback, $params);
        }
    }

    public function createOne($callback, array $params = [])
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('callback must be callable');
        }
        if ($this->pushed_size < $this->config['max_size']) {
            $link = call_user_func($callback, $params);
            $this->push($link);
        }
    }

    protected function push(Interfaces $link, bool $is_create = true)
    {
        $this->pool->push($link);
        if ($is_create) {
            $this->pushed_size++;
        }
    }

    /**
     * @return int
     */
    public function pushed()
    {
        return $this->pushed_size;
    }

    public function length()
    {
        return $this->pool->length();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get()
    {
        if (is_null($this->pool)) {
            throw new Exception("call init first");
        }
        $link = $this->pool->pop($this->config['timeout']);
        if (false === $link) {
            throw new Exception("Pop " . get_class($link) . " timeout");
        }
        defer(function () use ($link) { //释放
            $this->pool->push($link, false);
        });
        return $link;
    }
    //创建定时器保持链接
}