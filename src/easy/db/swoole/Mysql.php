<?php
namespace easy\db\swoole;

use easy\db\Interfaces;
use easy\exception\AttrNotFoundException;
use Swoole\Coroutine\MySQL\Statement;

class Mysql implements Interfaces
{

    //只读属性
    protected $config=[];//配置
    protected $connected=false;
    protected $connect_error='';
    protected $connect_errno=0;
    protected $error=[];
    protected $errno=0;
    protected $affected_rows=0;
    protected $insert_id=0;
    protected $handler=null;

    /**
     * @param $name
     * @return mixed
     * @throws AttrNotFoundException
     */
    public function __get($name)
    {
        if (!in_array($name, [
            'config',
            'connected',
            'connect_error',
            'connect_errno',
            'error',
            'errno',
            'affected_rows',
            'insert_id',
        ]))
        {
            throw new AttrNotFoundException('attr not found',$name);
        }
        return $this->$name;
    }

    //方法部分
    /**
     * @param array $config
     * @return bool
     */
    public function connect(array $config=null)
    {
        $this->config=(array)$config;

        $this->handler=new \Swoole\Coroutine\MySQL();

        $options=array_merge([
            'host'     => $config['host'],
            'port'     => $config['port'],
            'user'     => $config['username'],
            'password' => $config['password'],
            'database' => $config['database'],
            'charset'  => $config['charset'],
            'fetch_mode'=>true,
        ],(array)$config['options']);
        $this->handler->connect($options);
        $this->connected=$this->handler->connected;
        if(!$this->connected)
        {
            $this->connect_errno=$this->handler->connect_errno;
            $this->connect_error=$this->handler->connect_error;
            return false;
        }
        return true;
    }

    /**
     * @param string $sql
     * @param array $params
     * @return array|bool
     */
    public function query(string $sql,array $params=[])
    {
        if(false===$stat= $this->prepare($sql))
        {
            return false;
        }
        if(false===$stat=$this->runWithParams($stat,$params))
        {
            return false;
        }
        return $stat->fetchAll();
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @return int|bool
     */
    public function execute(string $sql,array $params=[])
    {
        if(false===$stat= $this->prepare($sql))
        {
            return false;
        }
        if(false===$stat=$this->runWithParams($stat,$params))
        {
            return false;
        }
        $this->affected_rows=$this->handler->affected_rows;
        if(preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $sql)) {
            $this->insert_id = $this->handler->insert_id;
        }
        return $this->affected_rows;
    }
    protected $param_map=[];
    /**
     * @param string $sql
     * @return bool|Statement
     */
    public function prepare(string $sql)
    {
        //把:开头的换成问号 并且记录顺序
        $array=explode(':',$sql);
        array_shift($array);
        foreach ($array as $item)
        {
            $this->param_map[]=':'.substr($item,0,strpos($item,' '));
        }
        if(!empty($this->param_map))
        {
            $sql=str_replace($this->param_map,'?',$sql);
        }
        if(false===$stat= $this->handler->prepare($sql))
        {
            $this->errno=$this->handler->errno;
            $this->error=[$this->handler->error];
            return false;
        }
        return $stat;
    }

    /**
     * @param Statement $stat
     * @param array|null $params
     * @return bool|Statement
     */
    protected function runWithParams(Statement $stat,array $params=null){
        $sort_params=array_map(function ($key)use($params){
            return $params[substr($key,1)];
        },$this->param_map);
        if(false===$stat->execute($sort_params))
        {
            $this->errno=$this->handler->errno;
            $this->error=[$this->handler->error];
            return false;
        }
        $this->param_map=[];
        return $stat;
    }

    //事务
    public function startTrans(){
        $this->handler->begin();
    }
    public function rollback(){
        $this->handler->rollback();
    }
    public function commit(){
        $this->handler->commit();
    }

}