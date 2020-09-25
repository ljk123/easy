<?php

namespace easy\response;

interface Interfaces
{
    public function setHeader(string $key, string $value);//设置header
    public function status(int $http_status_code);
    public function redirect(string $url, int $http_code);
    public function send(string $data);
}