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
        $res = $this->handle->get($key);
        return unserialize($res) ?: null;
    }

    public function set(string $key, $value, int $expire = 0)
    {
        if (is_null($value)) {
            return $this->handle->remove($key);
        }
        return $this->handle->set($key, serialize($value), $expire);
    }
}