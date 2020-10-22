<?php


namespace easy;


use easy\exception\ClassNotFoundException;
use easy\exception\MethodNotFoundException;
use easy\exception\ResponseException;
use easy\exception\RouteNotFoundException;
use easy\traits\Singleton;
use easy\utils\Str;
use ReflectionClass;
use ReflectionException;

/**
 * Class Dispatch
 * @property-read string $action
 * @property-read string $controller
 * @package easy
 */
class Dispatch
{
    use Singleton;

    private function __clone()
    {

    }

    private $app;
    private $controller;
    private $action;

    public function __get($name)
    {
        return $this->$name ?? null;
    }

    private function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 根据url定位控制器
     * hello/index 对应controller下 Hello::index
     * v1/hello/index 对应controller/v1下 Hello::index
     * v1/aaa/bbb/hello/index 对应controller/v1/aaa/bbb下 Hello::index
     * hello_world/index_aa_aa 对应controller下 HelloWorld::indexAaAa
     *
     * 精准匹配 如果未找到类直接抛出异常 方法通过 method_exists判断
     * @throws ClassNotFoundException
     * @throws MethodNotFoundException
     * @throws RouteNotFoundException
     * @throws ReflectionException
     */
    public function run()
    {
        $url = $this->app->request->getPath();
        $urls = explode('/', substr($url, 1));
        if (count($urls) < 2) {
            throw new RouteNotFoundException('route not found', $url);
        }
        //小写
        $this->action = $action = array_pop($urls);
        $controller = array_pop($urls);
        $path = empty($urls) ? '' : join('\\', $urls) . '\\';

        $action = Str::camel($action);
        $this->controller = $controller = Str::studly($controller);
        $namespace = '\\app\\controller\\' . $path;

        $class = $namespace . $controller;
        if (!class_exists($class)) {
            throw new ClassNotFoundException('controller not found', $class);
        }
        if (!method_exists($class, $action) && !method_exists($class, '__call')) {
            throw new MethodNotFoundException('action not found', $class . "::" . $action);
        }

        try {
            $ref_class = new ReflectionClass($class);
            $constructor = $ref_class->getConstructor();
            if ($constructor) {
                //如果存在构造方法
                $argv = Container::getInstance()->getArgv($constructor);
                $controller_instance = $ref_class->newInstanceArgs($argv);
            } else {
                $controller_instance = $ref_class->newInstance();
            }
            //前置操作
            if (method_exists($class, 'before')) {
                $method = $ref_class->getMethod('before');
                $argv = Container::getInstance()->getArgv($method);
                call_user_func_array([$controller_instance, 'before'], $argv);
            }
            if (method_exists($class, $action)) {
                $method = $ref_class->getMethod($action);
                $argv = Container::getInstance()->getArgv($method);
            } else {
                //__call
                $argv = [];
            }
            $result = call_user_func_array([$controller_instance, $action], $argv);
            //后置操作
            if (method_exists($class, 'after')) {
                $method = $ref_class->getMethod('after');
                $argv = Container::getInstance()->getArgv($method);
                call_user_func_array([$controller_instance, 'after'], $argv);
            }
        } catch (ResponseException $e) {
            $result = $e->getData();
        }

        //控制器返回内容
        $this->app->response->json($result);
    }
}