<?php


namespace easy;

use easy\exception\InvalidArgumentException;

/**
 * Class Response
 * @method void setHeader(string $key, string $value)
 * @method void status(int $http_status_code)
 * @method void redirect(string $url, int $http_code)
 * @method void send(string $data)
 * @package easy
 */

class Response
{
    protected $drive;
    private function __clone()
    {
    }
    private function __construct($type)
    {
        $class='easy\\response\\'.strtolower($type).'\\Response';
        if(!class_exists($class))
        {
            throw new InvalidArgumentException('response type does not supported:'.$type);
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
    public function json( $data,int $code=null){
        if(!is_string($data))
        {
            $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        }
        if(!is_null($code))
        {
            $this->status($code);
        }
        $this->send($data);
    }
}