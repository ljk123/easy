<?php


namespace easy\exception;

use easy\App;

class Handle
{
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
    public static function __make(App $app)
    {
        return new static($app);
    }

}