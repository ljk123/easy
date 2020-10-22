<?php


namespace easy\cache;


use easy\Container;

class Redis implements Interfaces
{
    /**@var \easy\Redis $handle */
    private $handle;

    public function __construct()
    {
        $this->handle = Container::getInstance()->get('redis');
    }

    public function get(string $key)
    {
        return $this->handle->get($key);
    }

    public function set(string $key, string $value, int $expire = 0)
    {
        return $this->handle->set($key, $value, $expire);
    }
}