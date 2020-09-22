<?php


namespace easy\request\swoole;


use easy\request\Interfaces;

class Request implements Interfaces
{
    public function getPath($name=null){}
    public function header($name=null){}
    public function server($name=null){}
    public function get($name=null){}
    public function post($name=null){}
    public function files($name=null){}
    public function content(){}
}