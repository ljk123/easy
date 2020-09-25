<?php


namespace easy;


use easy\traits\Singleton;

class Log
{
    const DEBUG=1;
    const INFO=2;
    const NOTICE=4;
    const WARNING=8;
    const ERROR=16;
    const map=[
        self::DEBUG=>'debug',
        self::INFO=>'info',
        self::NOTICE=>'notice',
        self::WARNING=>'warning',
        self::ERROR=>'error',
    ];
    use Singleton;
    private function __clone()
    {
        
    }
    private $root;
    private $cfg;
    private function __construct(App $app=null)
    {
        $this->root=$app->getRuntimePath().'logs'.DIRECTORY_SEPARATOR;
        $this->cfg=$app->config->load('log','log');
    }
    public function debug(string $msg)
    {
        $this->record($msg,self::DEBUG);
    }
    public function info(string $msg){

        $this->record($msg,self::INFO);
    }
    public function notice(string $msg){

        $this->record($msg,self::NOTICE);
    }
    public function warning(string $msg){

        $this->record($msg,self::WARNING);
    }
    public function error(string $msg){

        $this->record($msg,self::ERROR);
    }

    /**
     * @param string $msg
     * @param int $level
     * @param bool $is_force
     */
    public function write(string $msg,int $level=self::ERROR,bool $is_force=false){
        $this->onlog($msg,$level);
        $log_path=$this->getDir().date('d').'.log';
        if(($this->cfg['level']&$level)==$level || $is_force)
        {
            $str=date('[Y-m-d H:i:s]')."[".self::map[$level]."]\t$msg\n";
            file_put_contents($log_path,"{$str}",FILE_APPEND|LOCK_EX);
        }
    }

    /**
     * 记录到内存，在app结束时候写入文件
     * 如果有执行exit等结束语句会导致日志写入失败
     * @param string $msg
     * @param int $level
     * @param bool $is_force
     */
    public function record(string $msg,int $level=self::ERROR,bool $is_force=false){
       $this->onlog($msg,$level);
        if(($this->cfg['level']&$level)==$level || $is_force)
        {
            self::$log[]=date('[Y-m-d H:i:s]')."[".self::map[$level]."]\t$msg\n";
        }
    }
    protected function onlog(string $msg,int $level){
        if($this->cfg['onlog'] && is_callable($this->cfg['onlog']))
        {
            call_user_func($this->cfg['onlog'],$msg,$level);
        }
    }
    private static $log=[];
    public function save(){
        if(empty(self::$log)) return ;
        $log_path=$this->getDir().date('d').'.log';
        file_put_contents($log_path,join('',self::$log),FILE_APPEND|LOCK_EX);
        self::$log=[];
    }

    /**
     * @return string
     */
    protected function getDir(){
        $sub_path=date('y_m').DIRECTORY_SEPARATOR;
        $log_path=$this->root.$sub_path;
        if(!is_dir($log_path))
        {
            mkdir($log_path,0777,true);
        }
        return $log_path;
    }
}