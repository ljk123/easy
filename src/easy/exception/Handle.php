<?php


namespace easy\exception;

use easy\App;
use easy\Exception;
use easy\traits\Singleton;
use Throwable;

class Handle
{
    use Singleton;
    private $app;

    /**
     * @param Throwable $e
     * @throws Throwable
     */
    public function report(Throwable $e){
        if($this->app->config->get('app_debug'))
        {
            if($e instanceof Exception)
            {
                $this->app->response->send($e->getMessage().$e->getFile().":".$e->getLine());
            }
            else{
                throw $e;
            }
        }

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