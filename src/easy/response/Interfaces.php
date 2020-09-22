<?php

namespace easy\response;

interface Interfaces
{
    public function setHeader($key, $value);//设置header
    public function status( $http_status_code);
    public function redirect( $url,  $http_code);
    public function send( $data);
}