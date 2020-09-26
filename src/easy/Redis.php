<?php


namespace easy;

use easy\redis\Interfaces;
use easy\exception\InvalidArgumentException;
use easy\exception\RedisException;
use easy\traits\Singleton;

/**
 * Class Cache
 * @package easy
 */

class Redis
{
    use Singleton;
    /**
     * @var string
     */
    protected $driver_class;
    /**
     * @var array
     */
    protected $config;

    private function __clone()
    {
        
    }

    private function __construct(App $app)
    {
        $type=php_sapi_name() === 'cli' && class_exists('\Swoole\Coroutine')?'swoole':'fpm';
        $class='easy\\redis\\'.strtolower($type).'\\Redis';
        if(!class_exists($class))
        {
            throw new InvalidArgumentException('redis type does not supported:'.$type);
        }
        $this->driver_class=$class;
        $cfg=$app->config->load('redis','redis');
        //
        if($hosts=strpos($cfg['host'],','))
        {
            //分布式
            $database = explode(',',$cfg['db']);
            $password = explode(',',$cfg['password']);
            $port = explode(',',$cfg['port']);
            $config=[];
            foreach ($hosts as $k=>$host)
            {
                $config[]=[
                    'host' => $host,
                    'db' => $database[$k]??$database[0],
                    'password' => $password[$k]??$password[0],
                    'port'    => $port[$k]??$port[0],
                    'timeout'    => $config,
                ];
            }
        }
        else{
            $this->config=[$cfg];
        }
    }

    /**@var Interfaces $master_link*/
    protected $master_link=null;
    /**@var Interfaces $slave_link*/
    protected $slave_link=null;
    protected $error='';


    /**
     * @param bool $is_master
     * @return Interfaces
     * @throws RedisException
     */
    protected function initConnect(bool $is_master){
        if($is_master)
        {
            if(!empty($this->master_link))
            {
                return $this->master_link;
            }
            $config=$this->config[0];
            /**@var Interfaces $link*/
            $link=new $this->driver_class;
            if(false===$link->connect($config)){
                throw new RedisException($link->connect_error,$config);
            }
            return $this->master_link=$link;
        }
        else{
            if(!empty($this->slave_link))
            {
                return $this->slave_link;
            }
            //只有一个链接
            if(count($this->config)===1){
                return $this->slave_link=$this->initConnect(true);
            }
            //随机取一个从库配置
            /**@var array $config*/
            $config=mt_rand(1,count($this->config)-1);
            /**@var Interfaces $link*/
            $link=new $this->driver_class;
            if(false===$link->connect($config)){
                throw new RedisException($link->connect_error,$config);
            }
            return $this->master_link=$link;
        }
    }
    public function getError(){
        return $this->error;
    }

    /**
     * @param string $key
     * @param string $value
     * @param int $expire
     * @return bool
     */
    public function set(string $key,string $value,int $expire=0){
        try {
            if($expire)
            {
                $this->initConnect(true)->setex($key,$expire,$value);
            }
            else{
                $this->initConnect(true)->set($key,$value);
            }
        }catch (RedisException $e)
        {
            $this->error=$e->getMessage();
            return false;
        }
        return true;

    }

    /**
     * @param string $key
     * @return string
     */
    public function get(string $key){
        try {
            return $this->initConnect(false)->get($key);
        }catch (RedisException $e)
        {
            $this->error=$e->getMessage();
            return null;
        }
    }
}