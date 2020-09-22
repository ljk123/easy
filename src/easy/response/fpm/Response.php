<?php


namespace easy\response\fpm;

use easy\response\Interfaces;

class Response implements Interfaces
{
    protected $header=[
        'Content-Type'=>'application/json'
    ];
    protected $code=200;
    public function setHeader( $key, $value){
        $this->header[$key]=$value;
    }
    public function status( $http_status_code){
        $this->code=$http_status_code;
    }
    public function redirect( $url,  $http_code){

    }
    public function send( $data){
        if (!headers_sent() && !empty($this->header)) {
            // 发送状态码
            http_response_code($this->code);
            // 发送头部信息
            foreach ($this->header as $name => $val) {
                header($name . (!is_null($val) ? ':' . $val : ''));
            }
        }
        echo $data;
    }
}