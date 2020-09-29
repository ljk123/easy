<?php


namespace easy\db;

use easy\exception\AttrNotFoundException;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Class Mysql
 * 一个实例就是一个链接
 *
 * @property-read array $config
 * @property-read bool $connected
 * @property-read string $connect_error
 * @property-read int $connect_errno
 * @property-read array $error
 * @property-read int $errno
 * @property-read int $affected_rows
 * @property-read mixed $insert_id
 * @package easy\db
 */
class Mysql implements Interfaces,\easy\swoole\pool\Interfaces
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
    /**@var PDO $pdo*/
    protected $pdo=null;

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
        $dns=sprintf("mysql:dbname=%s;host=%s:%d",$config['database'],$config['host'],$config['port']);

        $options=array_merge([
            PDO::ATTR_CASE              =>  PDO::CASE_LOWER,
            PDO::ATTR_ERRMODE           =>  PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_ORACLE_NULLS      =>  PDO::NULL_NATURAL,
            PDO::ATTR_STRINGIFY_FETCHES =>  false,
            PDO::ATTR_TIMEOUT           =>  30,//好像没效果 以后在测试
        ],(array)$config['options']);

        //两个方式设置字符集
        $options[PDO::MYSQL_ATTR_INIT_COMMAND]='SET NAMES '.$config['charset'];
        $dns  .= ';charset='.$config['charset'];
        try {
            $this->pdo = new PDO($dns, $config['username'], $config['password'], $options);
        }catch (PDOException $e)
        {
            $this->connect_errno=$e->getCode();
            $this->connect_error=$e->getMessage();
            $this->pdo=null;
            return false;
        }
        $this->connected=true;
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
        return $stat->fetchAll(PDO::FETCH_ASSOC);
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
        $this->affected_rows=$stat->rowCount();
        if(preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $sql)) {
            $this->insert_id = $this->pdo->lastInsertId();
        }
        return $this->affected_rows;
    }

    /**
     * @param string $sql
     * @return bool|PDOStatement
     */
    public function prepare(string $sql)
    {
        if(false===$stat= $this->pdo->prepare($sql))
        {
            $this->errno=$this->pdo->errorCode();
            $this->error=$this->pdo->errorInfo();
            return false;
        }
        return $stat;
    }

    /**
     * @param PDOStatement $stat
     * @param array|null $params
     * @return bool|PDOStatement
     */
    protected function runWithParams(PDOStatement $stat,array $params=null){
        if(!empty($params))
        {
            foreach ($params as $key => $val) {
                $stat->bindValue($key, $val,is_int($val)?PDO::PARAM_INT:PDO::PARAM_STR);
            }
        }
        try {
            if(false===$stat->execute())
            {
                $this->errno=$stat->errorCode();
                $this->error=$stat->errorInfo();
                return false;
            }
        }
        catch (PDOException $e)
        {
            $this->errno=$e->getCode();
            $this->error=$e->getMessage();
            return false;
        }
        $this->last_use_time=time();
        return $stat;
    }

    //事务
    public function startTrans(){
        $this->pdo->beginTransaction();
    }
    public function rollback(){
        $this->pdo->rollBack();
    }
    public function commit(){
        $this->pdo->commit();
    }


    public function ping()
    {
        try{
            $this->pdo->getAttribute(PDO::ATTR_SERVER_INFO);
        } catch (PDOException $e) {
            if(strpos($e->getMessage(), 'MySQL server has gone away')!==false){
                return false;
            }
        }
        return true;
    }
    protected $last_use_time=0;
    public function lastUseTime()
    {
        return $this->last_use_time;
    }
    public function __destruct()
    {
        var_dump(__METHOD__);
    }
}