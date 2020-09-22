<?php


namespace easy;

use easy\app\Container;

class Config implements Container
{
    protected $config=[];
    /**
     * @var string
     */
    protected $path;

    private function __clone()
    {
    }
    private function __construct(string $path = null)
    {
        $this->path = $path ?: '';
        $config=$this->load('config');
        foreach ($config['config_files'] as $file=>$name)
        {
            $this->load($file,$name);
        }
    }
    public static function __make(App $app)
    {
        $path = $app->getAppPath().'config'.DIRECTORY_SEPARATOR;

        return new static($path);
    }

    /**
     * 加载配置文件（
     * @param  string $file 配置文件名
     * @param  string $name 一级配置名
     * @return array
     */
    public function load(string $file, string $name=null)
    {
        if (is_file($file)) {
            $filename = $file;
        } elseif (is_file($this->path . $file . '.php')) {
            $filename = $this->path . $file . '.php';
        }
        if (isset($filename)) {
            $config=include $filename;
            return $this->set($config,$name);
        }

        return $this->config;
    }

    /**
     * 设置配置参数 name为数组则为批量设置
     * @access public
     * @param array $config 配置参数
     * @param string $name 配置名
     * @return array
     */
    public function set(array $config, string $name = null)
    {
        if (!empty($name)) {
            if (isset($this->config[$name])) {
                $result = array_merge($this->config[$name], $config);
            } else {
                $result = $config;
            }

            $this->config[$name] = $result;
        } else {
            $result = $this->config = array_merge($this->config, array_change_key_case($config));
        }

        return $result;
    }


    /**
     * 检测配置是否存在
     * @access public
     * @param string $name 配置参数名（支持多级配置 .号分割）
     * @return bool
     */
    public function has(string $name)
    {
        return !is_null($this->get($name));
    }

    /**
     * 获取一级配置
     * @access protected
     * @param string $name 一级配置名
     * @return array
     */
    protected function pull(string $name)
    {
        $name = strtolower($name);

        return $this->config[$name] ?? [];
    }

    /**
     * 获取配置参数 为空则获取所有配置
     * @access public
     * @param string $name 配置参数名（支持多级配置 .号分割）
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $name = null, $default = null)
    {
        // 无参数时获取所有
        if (empty($name)) {
            return $this->config;
        }

        if (false === strpos($name, '.')) {
            return $this->pull($name);
        }

        $name    = explode('.', $name);
        $name[0] = strtolower($name[0]);
        $config  = $this->config;

        // 按.拆分成多维数组进行判断
        foreach ($name as $val) {
            if (isset($config[$val])) {
                $config = $config[$val];
            } else {
                return $default;
            }
        }

        return $config;
    }
}