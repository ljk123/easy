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
        $request_uri=$this->server('request_uri');
        $query_string=$this->server('query_string');
        if($query_string && false!==strpos($request_uri,$query_string))
        {
            $request_uri=substr($request_uri,0,-strlen($query_string)-1);
        }
        return $request_uri;
    }
    public function header(string $name=null){
        if(is_null($name))
        {
            return $this->request;
        }
        return $this->request[$name]??null;
    }
    public function server(string $name=null){
        if(is_null($name))
        {
            return $this->request->server;
        }
        $name=strtolower($name);
        return $this->request->server[$name]??null;
    }
    public function get(string $name=null){
        if(is_null($name))
        {
            return $this->request->get;
        }
        return $this->request->get[$name]??null;
    }
    public function post(string $name=null){
        if(is_null($name))
        {
            return $this->request->post;
        }
        return $this->request->post[$name]??null;
    }
    public function files(string $name=null){
        if(is_null($name))
        {
            return $this->request->files;
        }
        return $this->request->files[$name]??null;
    }
    public function content(){
        return $this->request->rawContent();
    }
}