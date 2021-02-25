<?php


namespace easy;

use easy\exception\InvalidArgumentException;
use easy\request\Interfaces;
use easy\traits\Singleton;

/**
 * Class Request
 * @method string getPath
 * @method array|string header(string $name = null)
 * @method array|string server(string $name = null)
 * @method array|string get(string $name = null)
 * @method array|string post(string $name = null)
 * @method array files(string $name = null)
 * @method string content
 * @package easy
 */
class Request
{
    use Singleton;

    /**@var Interfaces $driver */
    protected $driver;

    private function __construct()
    {
        $type = !defined('EASY_CONSOLE') && php_sapi_name() === 'cli' && class_exists('\Swoole\Coroutine') ? 'swoole' : 'fpm';
        $class = 'easy\\request\\' . strtolower($type) . '\\Request';
        if (!class_exists($class)) {
            throw new InvalidArgumentException('request type does not supported:' . $type);
        }
        $this->driver = new $class;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->driver, $name)) {
            return call_user_func_array([$this->driver, $name], $arguments);
        }
    }

    public function isPost()
    {
        return $this->driver->server('REQUEST_METHOD') === 'POST';
    }

    public function isGet()
    {
        return $this->driver->server('REQUEST_METHOD') === 'GET';
    }

    public function isPut()
    {
        return $this->driver->server('REQUEST_METHOD') === 'PUT';
    }

    public function isDelete()
    {
        return $this->driver->server('REQUEST_METHOD') === 'DELETE';
    }

    /**
     * @param array $field
     * @param string|null $default
     * @param string $method
     * @return array
     */
    public function only(array $field, string $default = null, string $method = 'post')
    {
        if (!in_array($method, ['post', 'get'])) {
            return null;
        }
        $data = $this->$method();
        $only = [];
        foreach ($field as $item) {
            $only[$item] = $data[$item] ?? $default;
        }
        return $only;
    }
}