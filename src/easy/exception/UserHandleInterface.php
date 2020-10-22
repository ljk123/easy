<?php


namespace easy\exception;

use Throwable;

interface UserHandleInterface
{
    public function report(Throwable $e);
}