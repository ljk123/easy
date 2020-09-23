<?php


namespace easy;


use easy\app\Container as ContainerInterface;
use easy\exception\ClassNotFoundException;
use easy\exception\InvalidArgumentException;
use easy\exception\MethodNotFoundException;
use easy\exception\RouteNotFoundException;
use easy\utils\Str;
use ReflectionClass;

class Dispatch implements ContainerInterface
{
    private function __clone()
    {

    }
    private $url;
    private $app;
    private function __construct(App $app)
    {
        $this->app=$app;
        $this->url=$app->request->getPath();
    }

    public static function __make(App $app)
    {
        return new static($app);
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
     * @throws InvalidArgumentException
     * @throws MethodNotFoundException
     * @throws RouteNotFoundException
     * @throws \ReflectionException
     */
    public function run(){
        //通过反射判断是否需要注入$request $reponse
        $urls=explode('/',substr($this->url,1));
        if(count($urls)<2)
        {
            throw new RouteNotFoundException('route not found',$this->url);
        }
        //小写
        $action=array_pop($urls);
        $controller=array_pop($urls);
        $path=empty($urls)?'':join('\\',$urls).'\\';

        $action = Str::camel($action);
        $controller = Str::studly($controller);

        $namespace = '\\app\\controller\\'.$path;

        $class=$namespace.$controller;
        if(!class_exists($class)){
            throw new ClassNotFoundException('controller not found',$class);
        }
        if(!method_exists($class,$action) && !method_exists($class,'__call'))
        {
            throw new MethodNotFoundException('action not found',$class."::".$action);
        }
        $ref_class=new ReflectionClass($class);
        $constructor=$ref_class->getConstructor();
        if($constructor){
            //如果存在构造方法
            $argv=Container::getInstance()->getArgv($constructor);
            $controller_instance=$ref_class->newInstanceArgs($argv);
        }
        else{
            $controller_instance=$ref_class->newInstance();
        }
        if(method_exists($class,$action))
        {
            $method=$ref_class->getMethod($action);
            $argv=Container::getInstance()->getArgv($method);
        }
        else{
            //__call
            $argv=[];
        }
        $result=call_user_func_array([$controller_instance,$action],$argv);
        //控制器返回内容
        $this->app->response->json($result);
    }
}