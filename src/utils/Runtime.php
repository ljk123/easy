<?php
namespace easy\utils;

class Runtime
{
    public static function sleep(float $sec){
        usleep(intval(floatval($sec)*1000000));
    }
    public static function microtime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
    public static function memory(){
        return memory_get_usage();
    }

}