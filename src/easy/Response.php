<?php


namespace easy;

use easy\exception\InvalidArgumentException;
use easy\traits\Singleton;

/**
 * Class Response
 * @method void setHeader(string $key, string $value)
 * @method void status(int $http_status_code)
 * @method void redirect(string $url, int $http_code)
 * @method static Response getInstance
 * @package easy
 */
class Response
{
    use Singleton;
    protected $driver;

    private function __clone()
    {
    }

    private function __construct()
    {
        $type = !defined('EASY_CONSOLE') && php_sapi_name() === 'cli' && class_exists('\Swoole\Coroutine') ? 'swoole' : 'fpm';
        $class = 'easy\\response\\' . strtolower($type) . '\\Response';
        if (!class_exists($class)) {
            throw new InvalidArgumentException('response type does not supported:' . $type);
        }
        $this->driver = new $class;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->driver, $name)) {
            return call_user_func_array([$this->driver, $name], $arguments);
        }
    }

    private $is_send = false;

    public function send(string $data)
    {
        if (!$this->is_send) {
            $this->is_send = true;//只发送一次
            call_user_func([$this->driver, 'send'], $data);
        }
    }

    public function json($data, int $code = null)
    {
        if (!is_string($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        if (!is_null($code)) {
            $this->status($code);
        }
        $this->send($data);
    }
}