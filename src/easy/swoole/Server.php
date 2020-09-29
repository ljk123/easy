<?php


namespace easy\swoole;

use easy\App;
use easy\Config;
use easy\Request;
use easy\Response;
use Swoole\Coroutine;
use Swoole\Process;

class Server
{
    public static function run($root_path=''){
        //读取swoole配置
        $swoole=Config::getInstance()->load($root_path.'app'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'swoole.php','swoole');
        $server=$swoole['server'];
        list($command,$options)=self::command();
        if(!$server['daemonize'] && in_array('-d',$options))
        {
            $server['daemonize']=1;
        }
        if($server['daemonize'])
        {
            $server['log_file']=realpath($root_path.'runtime').DIRECTORY_SEPARATOR.'swoole.log';
        }
        if(empty($server['pid_file']))
        {
            $server['pid_file']=realpath($root_path).DIRECTORY_SEPARATOR.'swoole.pid';
        }
        $is_alive=false;
        if(isset($server['pid_file']) && is_file($server['pid_file']))
        {
            $pid=file_get_contents($server['pid_file']);
            if(Process::kill($pid, 0))
            {
                $is_alive=true;
            }
        }
        if ($is_alive) {
            if ($command === 'start') {
                echo "server started at $pid".PHP_EOL;
                return;
            }
        } else {
            if ($command && $command !== 'start') {
                echo "server not run".PHP_EOL;
                return;
            }
        }
        switch ($command) {
            case 'start':
                break;
            case 'reload':
                Process::kill($pid,SIGUSR1);
                echo "sended reload command".PHP_EOL;
                return;
            case 'stop':
                Process::kill($pid,SIGTERM);
                echo "sended stop command".PHP_EOL;
                return;
            default:
                $usage = "
Usage: Commands 
Commands:\n
start\t\tStart worker.
restart\t\tStart master.
stop\t\tStop worker.
reload\t\tReload worker.
status\t\tWorker status.
\t\t-s speed info
\t\t-t time info
\t\t-c count info
\t\t-m count info

Use \"--help\" for more information about a command.\n";
                echo $usage;
                return;
        }

        $http = new \Swoole\Http\Server($swoole['http']['host'], $swoole['http']['port']);

        $http->set($server);
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
    protected static function command(){
        global $argv,$argc;
        $i=1;
        $options=[];
        $command='';
        while($argc-$i>0 && $command = $argv[$argc-$i])
        {
            if(substr($command,0,1)!=='-')
            {
                break;
            }
            $options[]=$command;
            $i++;
        }
        return [$command,$options];
    }
}