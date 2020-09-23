<?php


namespace easy;


use easy\exception\InvalidArgumentException;
use easy\traits\Singleton;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class Container
{
    use Singleton;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    protected $map = [];//映射类名
    protected $instances = [];//实例化过的

    public function bind($key, $obj)
    {
        $this->map[$key] = get_class($obj);
        $this->instances[$key] = $obj;
    }

    public function set($key, $value)
    {
        $this->map[$key] = $value;
    }
    public function has($key){
        if(!isset($this->map[$key]) && false === $key = array_search($key, $this->map))
        {
            return false;
        }
        return $key;
    }

    /**
     * 这个方法判断的依据 类的构造方法里面的参数
     * @param $key
     * @return object
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public function get($key)
    {
        if(false===$key=$this->has($key))
        {
            throw new InvalidArgumentException('key not found:' . $key);
        }
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }
        $class=$this->map[$key];
        // 反射类
        $reflect = new ReflectionClass($class);
        //先判断是否有make方法
        if($reflect->hasMethod('__make'))
        {
            $args=$this->getArgv($reflect->getMethod('__make'));
            return call_user_func_array([$class,'__make'],$args);
        }
        //先判断是否有make方法
        if($reflect->hasMethod('getInstance'))
        {
            $args=$this->getArgv($reflect->getMethod('getInstance'));
            return call_user_func_array([$class,'getInstance'],$args);
        }
        // 获取类的构造函数
        $c = $reflect->getConstructor();
        if (!$c) {
            // 如果没有构造函数，返回创建对象
            return $reflect->newInstance();
        }
        // 获取函数里面的参数
        $params = $c->getParameters();
        if (empty($params)) {
            // 如果构造函数里面没有参数，返回创建对象
            return $reflect->newInstance();
        }
        $args=[];
        // 遍历构造函数里面的参数
        foreach ($params as $param) {
            $class = $param->getClass();
            if (!$class) {

            } else {
                $args[] = $this->get($class->name);
            }
        }
        // 返回创建对象
        return $reflect->newInstanceArgs($args);
    }

    /**
     * 递归获取实例化参数 注入
     * @param ReflectionMethod $method
     * @return array
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function getArgv(ReflectionMethod $method)
    {
        $params=$method->getParameters();
        $args=[];
        foreach ($params as $param)
        {
            if($get_class=$param->getClass())
            {
                $name=$get_class->name;
                if($this->has($name)) {
                    $args[] = $this->get($name);
                }
                else{
                    //外部绑定
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
                    $this->bind($name,$instance);
                    $args[]=$instance;
                }
            }
            else{
                //如果没有默认值 报错
                if(!$param->isDefaultValueAvailable()){
                    throw new InvalidArgumentException("Failed to retrieve the default value:".$method->class."::".$method->name."->".$param->name);
                }
                $args[]=$param->getDefaultValue();
            }
        }
        return $args;
    }
}