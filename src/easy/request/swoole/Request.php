<?php


namespace easy\request\swoole;


use easy\request\Interfaces;

class Request implements Interfaces
{
    protected $request;
    //绑定swoole的resquest
    public function bind($request){
        $this->request=$request;
    }
    public function getPath($name=null){
        var_dump($this->server('request_uri'));
        return $this->server('request_uri');
    }
    public function header(string $name=null){}
    public function server(string $name=null){
        if(is_null($name))
        {
            return $this->request->server;
        }
        return $this->request->server[$name]??null;
    }
    public function get(string $name=null){}
    public function post(string $name=null){}
    public function files(string $name=null){}
    public function content(){}
}