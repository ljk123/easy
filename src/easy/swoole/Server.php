<?php


namespace easy\swoole;

use easy\App;
use easy\Config;
use easy\Request;
use easy\Response;
use Swoole\Coroutine;

class Server
{
    public static function run($root_path=''){
        //读取swoole配置
        $swoole=Config::getInstance()->load($root_path.'app'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'swoole.php','swoole');
        $http = new \Swoole\Http\Server($swoole['http']['host'], $swoole['http']['port']);
        $http->on('request', function ($request, $response) {
            Request::getInstance()->bind($request);
            Response::getInstance()->bind($response);
            $app=new App();
            $app->run();
        });
//一件协程
        Coroutine::set(['hook_flags'=>SWOOLE_HOOK_ALL|SWOOLE_HOOK_CURL]);
        $http->start();
    }
}