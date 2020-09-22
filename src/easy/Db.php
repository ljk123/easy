<?php


namespace easy;


use easy\app\Container;
use easy\db\fpm\Mysql;
use easy\exception\DbException;
use easy\exception\ErrorException;
use easy\exception\InvalidArgumentException;

/**
 * Class Db
 * 提供 query execute 方法 简单链式操作
 * @package easy
 */

class Db implements Container
{
    private function __clone()
    {
        
    }

    private function __construct($cfg)
    {
        //
        if($hosts=strpos($cfg['host'],','))
        {
            //分布式
            $database = explode(',',$cfg['database']);
            $username = explode(',',$cfg['username']);
            $password = explode(',',$cfg['password']);
            $port = explode(',',$cfg['port']);
            $config=[];
            foreach ($hosts as $k=>$host)
            {
                $config[]=[
                    'host' => $host,
                    'database' => isset($database[$k])?$database[$k]:$database[0],
                    'username' => isset($username[$k])?$username[$k]:$username[0],
                    'password' => isset($password[$k])?$password[$k]:$password[0],
                    'port'    => isset($port[$k])?$port[$k]:$port[0],
                    'options'            => $cfg['options'],
                    'charset'           => $cfg['charset'],
                    'prefix'            => $cfg['prefix'],
                ];
            }
        }
        else{
            $this->config=[$cfg];
        }
    }

    public static function __make(App $app)
    {
        $cfg=$app->config->load('database','database');
        return new static($cfg);
    }
    //属性部分
    protected $config;//配置
    /**@var Mysql $master_link*/
    protected $master_link=null;
    /**@var Mysql $slave_link*/
    protected $slave_link=null;
    protected $error='';

    /**
     * @param bool $is_master
     * @return Mysql
     * @throws Exception
     */
    protected function initConnect(bool $is_master){
        if($is_master)
        {
            if(!empty($this->master_link))
            {
                return $this->master_link;
            }
            $config=$this->config[0];
            $link=new Mysql();
            if(false===$link->connect($config)){
                //todo exception
                var_dump($link->error,$link->errno);
                throw new Exception();
            }
            return $this->master_link=$link;
        }
        else{
            if(!empty($this->slave_link))
            {
                return $this->slave_link;
            }
            //只有一个链接
            if(count($this->config)===1){
                return $this->slave_link=$this->initConnect(true);
            }
            //随机取一个从库配置
            $config=mt_rand(1,count($this->config)-1);
            $link=new Mysql();
            if(false===$link->connect($config)){
                //todo exception
                var_dump($link->error,$link->errno);
                throw new Exception();
            }
            return $this->master_link=$link;
        }
    }
    public function getError(){
        return $this->error;
    }


    //提供 query execute 方法
    public function query(string $sql,array $params=null)
    {
        $link=$this->initConnect(false);
        if(false===$result=$link->query($sql,$params))
        {
            $this->error='sqlerror:'.join(':',$link->error);
            return false;
        }
        return $result;
    }
    public function execute(string $sq,array $params=nulll){
        $link=$this->initConnect(true);
        if(false===$result=$link->execute($sql,$params))
        {
            $this->error='sqlerror:'.join(':',$link->error);
            return false;
        }
        return $result;
    }
    //事务
    public function startTrans(){
        return $this->initConnect(true)->startTrans();
    }
    public function rollback(){
        return $this->initConnect(true)->rollback();

    }
    public function commit(){
        return $this->initConnect(true)->commit();
    }
    //链式
    //  只提供简单的链式 复杂的自己走sql
    protected $options=[];

    /**
     * @param $table
     * @return $this
     * @throws InvalidArgumentException
     */
    public function table($table){
        if(is_string($table))
        {
            $table=explode(',',$table);
        }
        if(!is_array($table))
        {
            throw new InvalidArgumentException('table mast be string or array,'.gettype($table).' gieven');
        }
        $prefix=$this->config[0]['prefix'];
        $this->options['table']=$table;
        return $this;
    }
    public function alias($alias){
        if(is_string($alias))
        {
            $alias=explode(',',$alias);
        }
        if(!is_array($alias))
        {
            throw new InvalidArgumentException('alias mast be string or array,'.gettype($alias).' gieven');
        }
        $this->options['alias']=$alias;
        return $this;
    }
    public function join(string $table,string $alias,string $on,string $type=null){
        if(is_null($type) || !in_array($type=strtoupper($type),['left','right','inner']))
        {
            $type='LEFT';
        }
        if(empty($this->options['join']))
        {
            $this->options['join']=[];
        }
        $this->options['join'][]=compact('table','alias','on','type');
        return $this;
    }
    public function field(string $field){
        $this->options['field']=$field;
        return $this;
    }

    /**
     * 数组只实现=
     * 其他语法写原生语句
     * 多次调用逻辑是and
     * @param $where
     * @return $this
     */
    public function where($whereItem){
        if(empty($this->options['where']))
        {
            $this->options['where']=[
                'string'=>[],
                'params'=>[],
            ];
        }
        if(is_array($whereItem))
        {
            foreach ($whereItem as $key=>$val)
            {
                if(!is_string($val) && !is_numeric($val))
                {
                    throw new InvalidArgumentException('alias mast be string or numeric,'.gettype($val).' gieven');
                }
                $index=count($this->options['where']['params']);
                $key_index=str_replace(['.',],'_',$key).'_'.$index;
                $this->options['where']['string'][]="$key=:$key_index";
                $this->options['where']['params'][$key_index]=$val;
                $index++;
            }
        }
        elseif(is_string($whereItem)){
            $this->options['where']['string'][]=$whereItem;
        }
        else{
            throw new InvalidArgumentException('alias mast be string or array,'.gettype($whereItem).' gieven');
        }
        return $this;

    }
    public function limit(int $limit,int $offset=null){
        if(empty($offset))
        {
            $offset=$limit;
            $limit=0;
        }
        $this->options['limit']=compact('limit','offset');
        return $this;
    }
    public function page(int $page,int $size=null){
        if(is_null($size))
        {
            $size=20;
        }
        return $this->limit(($page-1)*$size,$size);
    }
    protected function parseOptions(){
        $options=$this->options;
        $this->options=[];
        //table
        if(empty($options['table']))
        {
            throw new InvalidArgumentException('table was miss of options');
        }
        if(!empty($options['alias']) && count($options['alias'])!==count($options['table']))
        {
            throw new InvalidArgumentException('count alias and table not exception');
        }
        $prefix=$this->config[0]['prefix'];
        $alisas=empty($options['alias'])?[]:$options['alias'];
        unset($options['alias']);
        $options['table']=join(',',array_map(function ($table,$alisa) use($prefix){
            return "`$prefix$table` $alisa";
        },$options['table'],$alisas));

        //join
        $options['join']=join('',array_map(function ($join) use ($prefix){
            return "{$join['type']} JOIN `$prefix{$join['table']}` {$join['alias']} on {$join['on']} ";
        },isset($options['join'])?$options['join']:[]));

        //field
        $options['field']=empty($options['field'])?'*':$options['field'];

        //where
        if(!empty($options['where']))
        {
            $options['params']=$options['where']['params'];
            $options['where']=join(' AND ',$options['where']['string']);
        }
        else{
            $options['params']=[];
            $options['where']='1';
        }
        //order
        $options['order']=empty($options['order'])?'':"ORDER BY {$options['order']}";
        //group
        $options['group']=empty($options['group'])?'':"GROUP BY {$options['group']}";
        //having
        $options['having']=empty($options['having'])?'':"HAVING {$options['having']}";

        //limit
        $options['limit']=empty($options['limit'])?'':"LIMIT {$options['limit']['limit']},{$options['limit']['offset']}";

        return $options;
    }
    public function order(string $order){
        $this->options['order']=$order;
        return $this;
    }
    public function group(string $group){
        $this->options['group']=$group;
        return $this;
    }
    public function having(string $having){
        $this->options['having']=$having;
        return $this;
    }

    /**
     * @param array $options
     * @return string
     */
    protected function optionSelectSql(array $options){
        $sql="SELECT _FIELD_ FROM _TABLE_ _JOIN_ WHERE _WHERE_ _GROUP_ _HAVING_ _ORDER_ _LIMIT_";
        return str_replace([
                '_FIELD_',
                '_TABLE_',
                '_JOIN_',
                '_WHERE_',
                '_WHERE_',
                '_GROUP_',
                '_HAVING_',
                '_ORDER_',
                '_LIMIT_',
            ],
            [
                $options['field'],
                $options['table'],
                $options['join'],
                $options['where'],
                $options['field'],
                $options['group'],
                $options['having'],
                $options['order'],
                $options['limit'],
            ],
            $sql
        );
    }

    //查询

    /**
     * @return array|bool
     * @throws InvalidArgumentException
     */
    public function find(){
        $this->limit(1);
        $options=$this->parseOptions();
        $sql=$this->optionSelectSql($options);
        if(false===$result=$this->query($sql,$options['params']))
        {
            return false;
        }
        if(empty($result))
        {
            return [];
        }
        return $result[0];
    }

    /**
     * @return array|bool
     * @throws InvalidArgumentException
     */
    public function select()
    {
        $options=$this->parseOptions();
        $sql=$this->optionSelectSql($options);
        if(false===$result=$this->query($sql,$options['params']))
        {
            return false;
        }
        if(empty($result))
        {
            return [];
        }
        return $result;
    }
    public function save()
    {

    }
    public function addall(){

    }
    public function value(){

    }
}