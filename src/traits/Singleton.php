<?php


namespace easy\traits;


trait Singleton
{
    private static $instance;

    /**
     * @param mixed ...$args
     * @return Singleton
     */
    public static function getInstance(...$args)
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self(...$args);
        }
        if(php_sapi_name() === 'cli' && class_exists('\Swoole\Coroutine')){
            //swoole环境自动释放 兼容fpm执行方式 防止内存泄漏
            defer(function (){
                self::free();
            });
        }
        return self::$instance;
    }

    /**
     * @return void
     */
    public static function free(){
        self::$instance=null;
    }
}