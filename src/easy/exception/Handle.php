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
     * @param Exception $e
     * @throws Exception
     */
    public function report($e){
        if($this->app->config->get('app_debug'))
        {
            throw $e;
        }
        $this->app->response->send($e->getMessage().$e->getFile().":".$e->getLine());
    }
    private function __clone()
    {
    }
    private function __construct(APP $app)
    {
        $this->app=$app;
    }
    protected function render($e){
        $this->app->response->json($e->getMessage().$e->getFile().":".$e->getLine());
    }

}