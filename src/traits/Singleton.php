<?php


namespace easy\traits;


trait Singleton
{
    private static $instance;
    protected static $co_instances = [];

    /**
     * @param mixed ...$args
     * @return self
     */
    public static function getInstance(...$args)
    {
        if (php_sapi_name() === 'cli' && class_exists('\Swoole\Coroutine')) {
            //swoole环境自动释放 兼容fpm执行方式 防止内存泄漏
            //创建协程单例
            $cid = \Swoole\Coroutine::getCid();
            //兼容非携程环境
            if ($cid > 0) {
                if (!isset(self::$co_instances[$cid])) {
                    self::$co_instances[$cid] = new self(...$args);
                    \Swoole\Coroutine::defer(function () use ($cid) {
                        unset(self::$co_instances[$cid]);
                    });
                }
                return self::$co_instances[$cid];
            }

        }
        //非swoole环境或者非协程环境
        if (!self::$instance instanceof self) {
            self::$instance = new self(...$args);
        }
        return self::$instance;
    }

    /**
     * @return void
     */
    public static function free()
    {
        self::$instance = null;
    }
}