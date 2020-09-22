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
        return self::$instance;
    }

    /**
     * @return void
     */
    public static function free(){
        self::$instance=null;
    }
}