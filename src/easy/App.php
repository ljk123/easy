<?php


namespace easy;

use easy\exception\ErrorException;
use easy\exception\Handle;
use easy\exception\InvalidArgumentException;
use ReflectionMethod;
use ReflectionClass;

/**
 * Class App
 * @property Config $config
 * @property Request $request
 * @property Response $response
 * @property Db $db
 * @property Handle $handle
 * @property Dispatch $dispatch
 * @method string getEasyPath
 * @method string getRootPath
 * @method string getAppPath
 * @method string getRuntimePath
 * @package easy
 */
class App
{
    /****路径相关 开始*******/
    protected $path=[
        'easy'=>'',
        'root'=>'',
        'app'=>'',
        'runtime'=>'',
    ];
    public function __construct($rootPath='')
    {
        $this->path['easy'] = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $this->path['root'] = $rootPath ? rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : $this->getDefaultRootPath();
        $this->path['app'] = $this->path['root'] . 'app' . DIRECTORY_SEPARATOR;
        $this->path['runtime'] = $this->path['root'] . 'runtime' . DIRECTORY_SEPARATOR;
    }
    protected function getDefaultRootPath(){
        return dirname($this->path['easy'], 4) . DIRECTORY_SEPARATOR;
    }

   public function __call($name, $arguments)
    {
        if(substr($name,0,3)==='get' && substr($name,-4)==='Path')
        {
            return $this->getPath(strtolower(substr($name,3,-4)));
        }
    }
    /**
     *
     * @param string $path
     * @return ?string
     */
    public function getPath($path)
    {
        if(!array_key_exists($path,$this->path))
        {
            return null;
        }
        return $this->path[$path];
    }
    /****路径相关 结束*******/

    /******容器相关 开始******/


    protected $binds=[
        'config'=>Config::class,
        'request'=>Request::class,
        'response'=>Response::class,
        'db'=>Db::class,
        'handle'=>Handle::class,
        'dispatch'=>Dispatch::class,
    ];
    protected $bindings=[];
    protected $bind_instances=[];//实例化过的
    protected $out_instances=[];//外部实力
    
    public function hasBindClass(string $class){
        return array_search($class,$this->binds);
    }


    /**
     * @param string $name
     * @param string $value
     * @return bool
     */
    public function set($name, $value='', $vars=[]){
        if(isset($this->bindings[$name]) )
        {
            return false;
        }
        else{
            if(isset($this->binds[$name]))
            {
                $class=$this->binds[$name];
                //省略第二个参数
                if(is_array($value) && empty($vars))
                {
                    $vars=$value;
                }
            }
            elseif(class_exists($value)){
                $class=$value;
            }
            elseif($value instanceof \Closure){
                $this->binds[$name]=call_user_func_array($value,$vars);
                return true;
            }
            else{
                return false;
            }

            //匿名函数 调用的时候才会被实例化
            $this->bindings[$name]= function () use ($class,$vars)
            {
                //首先调用__make
                if(method_exists($class,'__make'))
                {
                    return call_user_func_array([$class,'__make'],$vars);
                }
                elseif(method_exists($class,'getInstance'))
                {
                    return call_user_func_array([$class,'getInstance'],$vars);
                }
                else{
                    return new $class($vars);
                }

            };

            return true;
            
        }
    }
    //兼容魔方方法
    public function __set($key,$value){
        return $this->set($key,$value);
    }

    /**
     * @param $name
     * @param array $vars
     * @return mixed|null
     */
    public function get($name,$newInstances=false)
    {
        if(!array_key_exists($name,$this->bindings))
        {
            return null;
        }
        if(!$newInstances && isset($this->bind_instances[$name]))
        {
            return $this->bind_instances[$name];
        }
        //实例化
        return $this->bind_instances[$name]=call_user_func($this->bindings[$name]);
    }
    //兼容魔方方法
    public function __get($name){
        return $this->get($name);
    }

    /**
     * 递归获取实例化参数 注入
     * @param ReflectionMethod $method
     * @return array
     */
    public function getArgv(ReflectionMethod $method)
    {
        $params=$method->getParameters();
        $argv=[];
        foreach ($params as $param)
        {
            if($get_class=$param->getClass())
            {
                $name=$get_class->name;
                if(isset($this->out_instances[$name]))
                {
                    $instance=$this->out_instances[$name];
                }
                elseif($key=$this->hasBindClass($name))
                {
                    $instance=$this->get($key);
                }
                elseif($name===App::class)
                {
                    $instance=$this;
                }
                else{
                    $ref_class=new ReflectionClass($name);
                    $constructor=$ref_class->getConstructor();
                    if($constructor){
                        //如果存在构造方法
                        $argvs=$this->getArgv($constructor);
                        $instance=$ref_class->newInstanceArgs($argvs);
                    }
                    else{
                        $instance=$ref_class->newInstance();
                    }
                    //非app类加入map中
                    $this->out_instances[$name]=$instance;
                }
            }
            if(isset($instance)){
                $argv[]=$instance;
                unset($instance);
            }
            else{
                //如果没有默认值 报错
                if(!$param->isDefaultValueAvailable()){
                    throw new InvalidArgumentException("Failed to retrieve the default value:".$method->class."::".$method->name."->".$param->name);
                }
                $argv[]=$param->getDefaultValue();
            }
        }
        return $argv;
    }

    /******容器相关 结束******/


    public function runningInConsole()
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }
    public function run(){
        error_reporting(E_ALL);
        try {
            $this->init();
            $this->dispatch->run();
        }
        catch (\Exception $e)
        {
            //exception
        }
        catch (\Throwable $e)
        {
            //error
        }
        if(isset($e))
        {
            $this->handle->report($e);
        }
    }
    protected function init(){
        set_error_handler([$this, 'appError']);
        foreach ($this->binds as $k=>$v)
        {
            $this->set($k,[$this]);
        }
    }
    public function appError(int $errno, string $errstr, string $errfile , int $errline )
    {
        $exception = new ErrorException( $errstr,$errno, $errfile, $errline);
        if (error_reporting() & $errno) {
            throw $exception;
        }
    }
    
}