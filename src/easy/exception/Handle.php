<?php


namespace easy\exception;

use easy\App;
use easy\traits\Singleton;

class Handle
{
    use Singleton;
    private $app;

    /**
     * @param \Exception $exception
     * @throws \Exception
     */
    public function report($exception){
        throw $exception;
        //todo 渲染异常
        $this->app->response->send($exception->getMessage().$exception->getFile().":".$exception->getLine());
    }
    private function __clone()
    {
    }
    private function __construct(APP $app)
    {
        $this->app=$app;
    }

}