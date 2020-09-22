<?php


namespace easy;

use easy\app\Container;

/**
 * Class Request
 * @method srting getPath
 * @method array header
 * @method array server
 * @method array get
 * @method array post
 * @method array files
 * @method srting content
 * @package easy
 */
class Request implements Container
{
    protected $dirver;

    private function __construct($type)
    {
        $class='easy\\request\\'.strtolower($type).'\\Request';
        if(!class_exists($class))
        {
            //todo excption
        }
        $this->dirver=new $class;
    }

    public static function __make(App $app)
    {
        $type = $app->config->get('server_type');

        return new static($type);
    }
    public function __call($name, $arguments)
    {
        if(method_exists($this->dirver,$name))
        {
            return call_user_func_array([$this->dirver,$name],$arguments);
        }
    }
}