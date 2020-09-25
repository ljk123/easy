<?php


namespace easy\response\swoole;


use easy\response\Interfaces;

class Response implements Interfaces
{
    protected $response;
    public function bind($response){
        $this->response=$response;
    }

    protected $header=[
        'Content-Type'=>'application/json'
    ];
    protected $code=200;

    public function setHeader(string $key,string $value){
        $this->header[$key]=$value;
    }
    public function status(int $http_status_code){
        $this->code=$http_status_code;
    }

    public function redirect(string $url,int $http_code)
    {
        $this->response->redirect($url,$http_code);
    }

    public function send(string $data)
    {
        $this->response->status($this->code);
        // 发送头部信息
        foreach ($this->header as $name => $val) {
            $this->response->header($name,$val);
        }
        $this->response->end($data);
    }
}