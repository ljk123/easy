<?php


namespace easy\exception;

use easy\App;
use easy\traits\Singleton;
use Exception;

class Handle
{
    use Singleton;
    private $app;

    /**
     * @param Exception $exception
     * @throws Exception
     */
    public function report($exception){
        if($this->app->config->get('app_debug'))
        {
            throw $exception;
        }
        $this->app->response->send($exception->getMessage().$exception->getFile().":".$exception->getLine());
    }
    private function __clone()
    {
    }
    private function __construct(APP $app)
    {
        $this->app=$app;
    }
    protected function render($exception){
        $this->app->response->json($exception->getMessage().$exception->getFile().":".$exception->getLine());
    }

}