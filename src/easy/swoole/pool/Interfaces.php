<?php


namespace easy\swoole\pool;


interface Interfaces
{
    public function ping();
    public function lastUseTime();
}