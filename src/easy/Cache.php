<?php


namespace easy;

use easy\exception\InvalidArgumentException;
use easy\traits\Singleton;

/**
 * Class Cache
 * @method Cache getInstance
 * @package easy
 */
class Cache
{
    use Singleton;
    /**
     * @var string
     */
    protected $driver;
    /**
     * @var array
     */
    protected $config;

    private function __clone()
    {

    }

    private function __construct(App $app)
    {
        $cfg = $this->config = $app->config->load('cache', 'cache');
        if (strtolower($cfg['type']) == 'redis') {
            $this->driver = new \easy\cache\Redis();
        } else {
            throw new InvalidArgumentException('cache type does not supported:' . $cfg['type']);
        }
    }

    public function set(string $key, $value, int $expire = null)
    {
        $cache_key = $this->config['prefix'] . $key;
        if (is_null($expire)) {
            $expire = $this->config['expire'];
        }
        return $this->driver->set($cache_key, serialize($value), $expire);
    }

    public function get(string $key)
    {
        $cache_key = $this->config['prefix'] . $key;
        $res = $this->driver->get($cache_key);
        return $res ? unserialize($res) : null;
    }
}