<?php


namespace easy;

use easy\exception\InvalidArgumentException;
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
    protected $driver;

    private function __construct(string $type='fpm')
    {
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
}