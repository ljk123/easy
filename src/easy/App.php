<?php


namespace easy;

use easy\exception\ErrorException;
use easy\exception\Handle;
use easy\utils\Runtime;

/**
 * Class App
 * @property-read  Config $config
 * @property-read Request $request
 * @property-read Response $response
 * @property-read Db $db
 * @property-read Redis $redis
 * @property-read Cache $cache
 * @property-read Handle $handle
 * @property-read Dispatch $dispatch
 * @property-read Log $log
 * @property-read float $begin_time
 * @property-read float $begin_memory
 * @method string getEasyPath
 * @method string getRootPath
 * @method string getAppPath
 * @method string getRuntimePath
 * @package easy
 */
class App
{
    /****路径相关 开始*******/
    protected $path = [
        'easy' => '',
        'root' => '',
        'app' => '',
        'runtime' => '',
    ];

    public function __construct(string $rootPath = '')
    {
        $this->path['easy'] = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $this->path['root'] = $rootPath ? rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : $this->getDefaultRootPath();
        $this->path['app'] = $this->path['root'] . 'app' . DIRECTORY_SEPARATOR;
        $this->path['runtime'] = $this->path['root'] . 'runtime' . DIRECTORY_SEPARATOR;
        /**@var Container $container */
        $container = Container::getInstance();
        $container->bind('app', $this);
    }

    protected function getDefaultRootPath()
    {
        return dirname($this->path['easy'], 4) . DIRECTORY_SEPARATOR;
    }

    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) === 'get' && substr($name, -4) === 'Path') {
            return $this->getPath(strtolower(substr($name, 3, -4)));
        }
    }

    /**
     *
     * @param string $path
     * @return ?string
     */
    public function getPath($path)
    {
        if (!array_key_exists($path, $this->path)) {
            return null;
        }
        return $this->path[$path];
    }

    /****路径相关 结束*******/


    protected $binds = [
        'config' => Config::class,
        'request' => Request::class,
        'response' => Response::class,
        'db' => Db::class,
        'redis' => Redis::class,
        'cache' => Cache::class,
        'handle' => Handle::class,
        'dispatch' => Dispatch::class,
        'log' => Log::class,
    ];

    //容器魔方方法
    public function __get($name)
    {
        if (array_key_exists($name, $this->binds)) {
            return Container::getInstance()->get($name);
        }
        if ($name === 'begin_time') {
            return $this->begin_time;
        }
        if ($name === 'begin_memory') {
            return $this->begin_memory;
        }
        return null;
    }

    //开始的时间
    protected $begin_time;
    protected $begin_memory;

    public function run()
    {
        $this->begin_time = Runtime::microtime();
        $this->begin_memory = Runtime::memory();
        error_reporting(E_ALL);
        try {
            $this->init();
            $this->dispatch->run();
        } catch (\Throwable $e) {
            $this->handle->report($e);
        }
        $this->log->save();
    }

    protected function init()
    {
        set_error_handler([$this, 'appError']);
        /**@var Container $container */
        $container = Container::getInstance();
        foreach ($this->binds as $k => $v) {
            $container->set($k, $v);
        }
        $container->get('log');
    }

    /**
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @throws ErrorException
     */
    public function appError(int $errno, string $errstr, string $errfile, int $errline)
    {
        $e = new ErrorException($errstr, $errno, $errfile, $errline);
        if (error_reporting() & $errno) {
            throw $e;
        }
    }
}