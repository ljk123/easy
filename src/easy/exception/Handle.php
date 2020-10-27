<?php


namespace easy\exception;

use easy\App;
use easy\Exception;
use easy\traits\Singleton;
use Throwable;

/**
 * Class Handle
 * @method Handle getInstance
 * @package easy\exception
 */
class Handle
{
    use Singleton;
    private $app;

    /**
     * @param Throwable $e
     * @throws Throwable
     */
    public function report(Throwable $e)
    {
        if ($e instanceof Exception) {
            $exception_handle = $this->app->config->get('exception_handle');
            if ($exception_handle && class_exists($exception_handle)) {
                $handle = (new $exception_handle);
                if ($handle instanceof UserHandleInterface) {
                    try {
                        if (call_user_func([$handle, 'report'], $e)) {
                            return;
                        }
                    } catch (\Exception $e) {
                        //防止操作失误 覆盖$e

                    }
                }
            }
        }
        if ($this->app->config->get('app_debug')) {
            $line = $e->getLine();
            $script = $this->getScript($e->getFile(), $line);

            $html = $this->exception_html($e, $script);
            $this->app->response->setHeader('Content-Type', 'text/html');
            $this->app->response->status($e->getCode() ?: 500);
            $this->app->response->send($html);

        } else {
            $this->app->response->status($e->getCode() ?: 500);
            $error = [
                'msg' => $e->getMessage() . $e->getFile() . ":" . $e->getLine(),
                'data' => $e->getTraceAsString(),
                'status' => 0,
            ];
            $this->app->response->json(
                $error);
        }

    }

    private function __clone()
    {
    }

    private function __construct(APP $app)
    {
        $this->app = $app;
    }

    protected function getScript(string $file, int $line)
    {
        $contents = file($file) ?: [];
        $first = ($line - 9 > 0) ? $line - 9 : 1;
        return [
            'first' => $first,
            'source' => array_slice($contents, $first - 1, 19),
            'line' => $line,
        ];
    }

    protected function exception_html(Throwable $e, array $script)
    {
        ob_start();
        include "exception_html.php";
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}