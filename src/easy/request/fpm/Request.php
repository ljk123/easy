<?php


namespace easy\request\fpm;


use easy\request\Interfaces;
use easy\utils\Str;

class Request implements Interfaces
{
    public function getPath(){
        return $this->server('REQUEST_URI');
    }
    public function header($name=null){
        $server=$this->server();
        $header=[];
        foreach ($server as $k=>$v)
        {
            if(substr($k,0,5)==='HTTP_')
            {
                $header[Str::studly(strtolower(substr($k,5)))]=$v;
            }
        }
        if(is_null($name))
        {
            return $header;
        }
        return isset($header[$name])?$header[$name]:null;
    }
    public function server($name=null){

        if(is_null($name))
        {
            return $_SERVER;
        }
        return isset($_SERVER[$name])?$_SERVER[$name]:null;
    }
    public function get($name=null){
        if(is_null($name))
        {
            return $_GET;
        }
        return isset($_GET[$name])?$_GET[$name]:null;
    }
    public function post($name=null){
        $post= !empty($_POST)?$_POST:json_decode($this->content(),true);
        if(is_null($name))
        {
            return $post;
        }
        return isset($post[$name])?$post[$name]:null;
    }
    public function files($name=null){
        if(is_null($name))
        {
            return $_FILES;
        }
        return isset($_FILES[$name])?$_FILES[$name]:null;
    }
    public function content(){
        return file_get_contents("php://input");
    }
}


//    protected $input;
//
//    protected $param;
//    protected $post;
//    protected $get;
//    protected $request;
//    protected $server;
//
//    protected $filter;
//
//    public function __construct()
//    {
//        $this->input=file_get_contents("php://input");
//        $this->post=$_POST?$_POST:json_decode($this->input,true);
//        $this->get=$_GET;
//        $this->request=$_REQUEST;
//        $this->server=$_SERVER;
//    }
//    public function __call($name, $arguments)
//    {
//        if(in_array($name,['param','post','get','request']))
//        {
//            if (is_array($name)) {
//                return $this->only(...$arguments);
//            }
//        }
//        return $this->input($this->$name,...$arguments);
//
//    }
//    /**
//     * 获取变量 支持过滤和默认值
//     * @access public
//     * @param  array        $data 数据源
//     * @param  string|false $name 字段名
//     * @param  mixed        $default 默认值
//     * @param  string|array $filter 过滤函数
//     * @return mixed
//     */
//    public function input($data = [], $name = '', $default = null, $filter = '')
//    {
//        if(empty($data))
//        {
//            return $default;
//        }
//        if (false === $name) {
//            // 获取原始数据
//            return $data;
//        }
//
//        $data = $this->filterData($data, $filter, $name, $default);
//
//        return $data;
//    }
//
//
//    public function only(array $name, $data = 'param', $filter = ''): array
//    {
//        $data = is_array($data) ? $data : $this->$data();
//
//        $item = [];
//        foreach ($name as $key => $val) {
//
//            if (is_int($key)) {
//                $default = null;
//                $key     = $val;
//                if (!isset($data[$key])) {
//                    continue;
//                }
//            } else {
//                $default = $val;
//            }
//
//            $item[$key] = $this->filterData($data[$key] ?? $default, $filter, $key, $default);
//        }
//
//        return $item;
//    }
//
//    protected function filterData($data, $filter, $name, $default)
//    {
//        // 解析过滤器
//        $filter = $this->getFilter($filter, $default);
//
//        if (is_array($data)) {
//            array_walk_recursive($data, [$this, 'filterValue'], $filter);
//        } else {
//            $this->filterValue($data, $name, $filter);
//        }
//
//        return $data;
//    }
//    protected function getFilter($filter, $default): array
//    {
//        if (is_null($filter)) {
//            $filter = [];
//        } else {
//            $filter = $filter ?: $this->filter;
//            if (is_string($filter) && false === strpos($filter, '/')) {
//                $filter = explode(',', $filter);
//            } else {
//                $filter = (array) $filter;
//            }
//        }
//
//        $filter[] = $default;
//
//        return $filter;
//    }
//
//    /**
//     * 递归过滤给定的值
//     * @access public
//     * @param  mixed $value 键值
//     * @param  mixed $key 键名
//     * @param  array $filters 过滤方法+默认值
//     * @return mixed
//     */
//    public function filterValue(&$value, $key, $filters)
//    {
//        $default = array_pop($filters);
//
//        foreach ($filters as $filter) {
//            if (is_callable($filter)) {
//                // 调用函数或者方法过滤
//                $value = call_user_func($filter, $value);
//            } elseif (is_scalar($value)) {
//                if (is_string($filter) && false !== strpos($filter, '/')) {
//                    // 正则过滤
//                    if (!preg_match($filter, $value)) {
//                        // 匹配不成功返回默认值
//                        $value = $default;
//                        break;
//                    }
//                } elseif (!empty($filter)) {
//                    // filter函数不存在时, 则使用filter_var进行过滤
//                    // filter为非整形值时, 调用filter_id取得过滤id
//                    $value = filter_var($value, is_int($filter) ? $filter : filter_id($filter));
//                    if (false === $value) {
//                        $value = $default;
//                        break;
//                    }
//                }
//            }
//        }
//
//        return $value;
//    }