<?php

namespace easy\request;

interface Interfaces
{
    public function getPath();
    public function header(string $name=null);//相当于 PHP 的 $_SERVER 数组。包含了 HTTP 请求的方法，URL 路径，客户端 IP 等信息。
    public function server(string $name=null);//$_SERVER
    public function get(string $name=null);//$_GET
    public function post(string $name=null);//$_POST 或者json('php://input')
    public function files(string $name=null);//$_FILES
    public function content();//('php://input')
}