<?php


namespace easy;

use easy\exception\InvalidArgumentException;

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
    protected $drive;

    private function __construct($type)
    {
        $class='easy\\request\\'.strtolower($type).'\\Request';
        if(!class_exists($class))
        {
            throw new InvalidArgumentException('request type does not supported:'.$type);
        }
        $this->drive=new $class;
    }

    public static function __make(App $app)
    {
        $type = $app->config->get('server_type');

        return new static($type);
    }
    public function __call($name, $arguments)
    {
        if(method_exists($this->drive,$name))
        {
            return call_user_func_array([$this->drive,$name],$arguments);
        }
    }
}