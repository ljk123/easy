<?php


namespace easy;

use easy\db\Interfaces;
use easy\db\Mysql;
use easy\exception\DbException;
use easy\exception\InvalidArgumentException;
use easy\swoole\pool\Pool;
use easy\traits\Chains;
use easy\traits\Singleton;

/**
 * Class Db
 * 提供 query execute 方法 简单链式操作
 * @method Db getInstance
 * @package easy
 */
class Db
{
    use Singleton, Chains;

    private function __clone()
    {

    }

    private function __construct()
    {
        $app = Container::getInstance()->get('app');
        $cfg = $app->config->load('database', 'database');
        $is_swoole = !defined('EASY_CONSOLE') && php_sapi_name() === 'cli' && class_exists('\Swoole\Coroutine');
        if ($is_swoole) {
            $config = $app->config->load('swoole', 'swoole');
            $mysql = $config['mysql'];
            if ($mysql['pool']) {
                $cfg['pool'] = $mysql;
            }
        }
        //
        if ($hosts = strpos($cfg['host'], ',')) {
            //分布式
            $database = explode(',', $cfg['database']);
            $username = explode(',', $cfg['username']);
            $password = explode(',', $cfg['password']);
            $port = explode(',', $cfg['port']);
            $config = [];
            foreach ($hosts as $k => $host) {
                $config[] = [
                    'host' => $host,
                    'database' => $database[$k] ?? $database[0],
                    'username' => $username[$k] ?? $username[0],
                    'password' => $password[$k] ?? $password[0],
                    'port' => $port[$k] ?? $port[0],
                    'options' => $cfg['options'],
                    'charset' => $cfg['charset'],
                    'prefix' => $cfg['prefix'],
                ];
            }
            $this->config = $config;
        } else {
            $this->config = [$cfg];
        }
        $this->prefix = $this->config[0]['prefix'];
        $this->init();
    }

    //属性部分
    protected $config;//配置
    /**@var Interfaces $master_link */
    protected $master_link = null;
    /**@var Interfaces $slave_link */
    protected $slave_link = null;
    private $lately_is_master = false;
    //对外暴露前缀
    private $prefix;

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param bool $is_master
     * @return Interfaces
     * @throws Exception
     */
    public function initConnect(bool $is_master)
    {
        $this->lately_is_master = $is_master;
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

    /**
     * @param $config
     * @return Mysql|mixed
     * @throws DbException
     * @throws Exception
     */
    protected function newConnect($config)
    {

        if (isset($config['pool'])) {
            try {

                //创建连接池
                /**@var Pool $pool */
                $pool = Pool::getInstance($config['pool']);

                if ($pool->length() > 0) {
                } elseif ($pool->pushed() === 0) {
                    //创建
                    $pool->create(function ($config) {
                        $link = new Mysql();
                        if (false === $link->connect($config)) {
                            throw new DbException($link->connect_error, $config);
                        }
                        return $link;
                    }, $config);
                } elseif ($pool->length() < $config['pool']['max_size']) {
                    $pool->createOne(function ($config) {
                        $link = new Mysql();
                        if (false === $link->connect($config)) {
                            throw new DbException($link->connect_error, $config);
                        }
                        return $link;
                    }, $config);
                }
                $link = $pool->get();
            } catch (InvalidArgumentException $e) {
                throw new DbException($e->getMessage(), $config);
            }
        } else {
            $link = new Mysql();
            if (false === $link->connect($config)) {
                throw new DbException($link->connect_error, $config);
            }
        }
        return $link;
    }


    //提供 query execute 方法

    /**
     *
     * @param string $sql
     * @param array $params
     * @return array|bool
     */
    public function query(string $sql, array $params = [])
    {
        $link = $this->initConnect(false);
        if (false === $result = $this->initConnect(false)->query($sql, $params)) {
            $this->error = 'sqlerror:' . join(':', $link->error);
            return false;
        }
        return $result;
    }

    /**
     *
     * @param string $sql
     * @param array $params
     * @return bool|int
     */
    public function execute(string $sql, array $params = nulll)
    {
        $link = $this->initConnect(true);
        if (false === $result = $link->execute($sql, $params)) {
            $this->error = 'sqlerror:' . join(':', $link->error);
            return false;
        }
        return $result;
    }

    //事务

    public function startTrans()
    {
        return $this->initConnect(true)->startTrans();
    }

    public function rollback()
    {
        return $this->initConnect(true)->rollback();

    }

    public function commit()
    {
        return $this->initConnect(true)->commit();
    }
}