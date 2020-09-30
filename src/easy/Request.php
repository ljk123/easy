<?php


namespace easy;

use easy\exception\InvalidArgumentException;
use easy\request\Interfaces;
use easy\traits\Singleton;

/**
 * Class Request
 * @method string getPath
 * @method array header
 * @method array server
 * @method array get
 * @method array post
 * @method array files
 * @method string content
 * @package easy
 */
class Request
{
    use Singleton;
    /**@var Interfaces $driver*/
    protected $driver;

    private function __construct()
    {
        $type = !defined('EASY_CONSOLE') && php_sapi_name() === 'cli' && class_exists('\Swoole\Coroutine')?'swoole':'fpm';
        $class='easy\\request\\'.strtolower($type).'\\Request';
        if(!class_exists($class))
        {
            throw new InvalidArgumentException('request type does not supported:'.$type);
        }
        $this->driver=new $class;
    }

    public function __call($name, $arguments)
    {
        if(method_exists($this->driver,$name))
        {
            return call_user_func_array([$this->driver,$name],$arguments);
        }
    }

    public function isPost():bool
    {
        return $this->driver->server('REQUEST_METHOD')==='POST';
    }
    public function isGet():bool
    {
        return $this->driver->server('REQUEST_METHOD')==='GET';
    }
    public function isPut():bool
    {
        return $this->driver->server('REQUEST_METHOD')==='PUT';
    }
    public function isDelete():bool
    {
        return $this->driver->server('REQUEST_METHOD')==='DELETE';
    }
}