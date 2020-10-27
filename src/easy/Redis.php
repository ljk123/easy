<?php


namespace easy;

use easy\exception\RedisException;
use easy\redis\Interfaces;
use easy\swoole\pool\Pool;
use easy\traits\Singleton;

/**
 * Class Cache
 * @method Redis getInstance
 * @package easy
 */
class Redis
{
    use Singleton;
    /**
     * @var array
     */
    protected $config;

    private function __clone()
    {

    }

    private function __construct(App $app)
    {
        $cfg = $app->config->load('redis', 'redis');
        $is_swoole = !defined('EASY_CONSOLE') && php_sapi_name() === 'cli' && class_exists('\Swoole\Coroutine');
        if ($is_swoole) {
            $config = $app->config->load('swoole', 'swoole');
            $pool = $config['redis'];
            if ($pool['pool']) {
                $cfg['pool'] = $pool;
            }
        }
        if ($hosts = strpos($cfg['host'], ',')) {
            //分布式
            $database = explode(',', $cfg['db']);
            $password = explode(',', $cfg['password']);
            $port = explode(',', $cfg['port']);
            $config = [];
            foreach ($hosts as $k => $host) {
                $config[] = [
                    'host' => $host,
                    'db' => $database[$k] ?? $database[0],
                    'password' => $password[$k] ?? $password[0],
                    'port' => $port[$k] ?? $port[0],
                    'timeout' => $config,
                ];
            }
        } else {
            $this->config = [$cfg];
        }
    }

    /**@var Interfaces $master_link */
    protected $master_link = null;
    /**@var Interfaces $slave_link */
    protected $slave_link = null;
    protected $error = '';


    /**
     * @param bool $is_master
     * @return Interfaces
     * @throws RedisException
     * @throws Exception
     */
    protected function initConnect(bool $is_master)
    {
        if ($is_master) {
            if (!empty($this->master_link)) {
                return $this->master_link;
            }
            $config = $this->config[0];
            /**@var Interfaces $link */
            $link = $this->newConnect($config);
            return $this->master_link = $link;
        } else {
            if (!empty($this->slave_link)) {
                return $this->slave_link;
            }
            //只有一个链接
            if (count($this->config) === 1) {
                return $this->slave_link = $this->initConnect(true);
            }
            //随机取一个从库配置
            /**@var array $config */
            $config = mt_rand(1, count($this->config) - 1);
            /**@var Interfaces $link */
            $link = $this->newConnect($config);
            return $this->master_link = $link;
        }
    }

    public function getError()
    {
        return $this->error;
    }


    protected function newConnect($config)
    {
        if (!empty($config['pool'])) {
            //创建连接池
            /**@var Pool $pool */
            $pool = Pool::getInstance($config['pool']);
            if ($pool->length() > 0) {
            } elseif ($pool->pushed() === 0) {
                //创建
                for ($i = 0; $i < $config['pool']['min_size']; $i++) {
                    $pool->create(function ($config) {
                        $link = new redis\Redis();
                        if (false === $link->connect($config)) {
                            throw new RedisException($link->connect_error, $config);
                        }
                        return $link;
                    }, $config);
                }
            } elseif ($pool->length() < $config['pool']['max_size']) {
                $pool->createOne(function ($config) {
                    $link = new redis\Redis();
                    if (false === $link->connect($config)) {
                        throw new RedisException($link->connect_error, $config);
                    }
                    return $link;
                }, $config);
            }
            $link = $pool->get();
        } else {
            $link = new redis\Redis();
            if (false === $link->connect($config)) {
                throw new RedisException($link->connect_error, $config);
            }
        }
        return $link;
    }

    /**
     * @param string $key
     * @param string $value
     * @param int $expire
     * @return bool
     */
    public function set(string $key, string $value, int $expire = 0)
    {
        try {
            if ($expire) {
                $this->initConnect(true)->setex($key, $expire, $value);
            } else {
                $this->initConnect(true)->set($key, $value);
            }
        } catch (RedisException $e) {
            $this->error = $e->getMessage();
            return false;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return null;
        }
        return true;

    }

    /**
     * @param string $key
     * @return string
     */
    public function get(string $key)
    {
        try {
            return $this->initConnect(false)->get($key);
        } catch (RedisException $e) {
            $this->error = $e->getMessage();
            return null;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return null;
        }
    }
}