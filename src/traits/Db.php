<?php


namespace easy\traits;

use easy\Container;
use easy\db\Interfaces;
use easy\db\Mysql;
use easy\exception\DbException;
use easy\exception\InvalidArgumentException;
use easy\swoole\pool\Pool;

/**
 * Class Db
 * 提供 query execute 方法 简单链式操作
 * @package easy
 */
trait Db
{
    protected $inited=false;
    private function init()
    {
        $app = Container::getInstance()->get('app');
        $cfg = $app->config->load('database', 'database');
        $is_swoole = !defined('EASY_CONSOLE') && php_sapi_name() === 'cli' && class_exists('\Swoole\Coroutine');
        if ($is_swoole) {
            $config = $app->config->load('swoole', 'swoole');
            $mysql = $config['mysql'];
            if ($mysql['pool']) {
                $cfg['pool'] = $mysql;
            }
        }
        //
        if ($hosts = strpos($cfg['host'], ',')) {
            //分布式
            $database = explode(',', $cfg['database']);
            $username = explode(',', $cfg['username']);
            $password = explode(',', $cfg['password']);
            $port = explode(',', $cfg['port']);
            $config = [];
            foreach ($hosts as $k => $host) {
                $config[] = [
                    'host' => $host,
                    'database' => $database[$k] ?? $database[0],
                    'username' => $username[$k] ?? $username[0],
                    'password' => $password[$k] ?? $password[0],
                    'port' => $port[$k] ?? $port[0],
                    'options' => $cfg['options'],
                    'charset' => $cfg['charset'],
                    'prefix' => $cfg['prefix'],
                ];
            }
            $this->config = $config;
        } else {
            $this->config = [$cfg];
        }
        $this->inited=true;
    }

    //属性部分
    protected $config;//配置
    /**@var Interfaces $master_link */
    protected $master_link = null;
    /**@var Interfaces $slave_link */
    protected $slave_link = null;
    protected $error = '';

    /**
     * @param bool $is_master
     * @return Interfaces
     * @throws Exception
     */
    protected function initConnect(bool $is_master)
    {
        if ($is_master) {
            if (!empty($this->master_link)) {
                return $this->master_link;
            }
            $config = $this->config[0];
            /**@var Interfaces $link */
            $link = $this->newConnect($config);
            return $this->master_link = $link;
        } else {
            if (!empty($this->slave_link)) {
                return $this->slave_link;
            }
            //只有一个链接
            if (count($this->config) === 1) {
                return $this->slave_link = $this->initConnect(true);
            }
            //随机取一个从库配置
            /**@var array $config */
            $config = mt_rand(1, count($this->config) - 1);
            /**@var Interfaces $link */
            $link = $this->newConnect($config);
            return $this->master_link = $link;
        }
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * @param $config
     * @return Mysql|mixed
     * @throws DbException
     * @throws Exception
     */
    protected function newConnect($config)
    {

        if (isset($config['pool'])) {
            try {

                //创建连接池
                /**@var Pool $pool */
                $pool = Pool::getInstance($config['pool']);

                if ($pool->length() > 0) {
                } elseif ($pool->pushed() === 0) {
                    //创建
                    $pool->create(function ($config) {
                        $link = new Mysql();
                        if (false === $link->connect($config)) {
                            throw new DbException($link->connect_error, $config);
                        }
                        return $link;
                    }, $config);
                } elseif ($pool->length() < $config['pool']['max_size']) {
                    $pool->createOne(function ($config) {
                        $link = new Mysql();
                        if (false === $link->connect($config)) {
                            throw new DbException($link->connect_error, $config);
                        }
                        return $link;
                    }, $config);
                }
                $link = $pool->get();
            } catch (InvalidArgumentException $e) {
                throw new DbException($e->getMessage(), $config);
            }
        } else {
            $link = new Mysql();
            if (false === $link->connect($config)) {
                throw new DbException($link->connect_error, $config);
            }
        }
        return $link;
    }


    //提供 query execute 方法

    /**
     *
     * @param string $sql
     * @param array $params
     * @return array|bool
     * @throws Exception
     */
    public function query(string $sql, array $params = [])
    {
        $link = $this->initConnect(false);
        if (false === $result = $this->initConnect(false)->query($sql, $params)) {
            $this->error = 'sqlerror:' . join(':', $link->error);
            return false;
        }
        return $result;
    }

    /**
     *
     * @param string $sql
     * @param array $params
     * @return bool|int
     * @throws Exception
     */
    public function execute(string $sql, array $params = nulll)
    {
        $link = $this->initConnect(true);
        if (false === $result = $link->execute($sql, $params)) {
            $this->error = 'sqlerror:' . join(':', $link->error);
            return false;
        }
        return $result;
    }
    //事务

    /**
     * @throws Exception
     */
    public function startTrans()
    {
        return $this->initConnect(true)->startTrans();
    }

    /**
     * @throws Exception
     */
    public function rollback()
    {
        return $this->initConnect(true)->rollback();

    }

    /**
     * @throws Exception
     */
    public function commit()
    {
        return $this->initConnect(true)->commit();
    }
    //链式
    //  只提供简单的链式 复杂的自己走sql
    protected $options = [];

    /**
     * @param $table
     * @return $this
     * @throws InvalidArgumentException
     */
    public function table($table)
    {
        if (is_string($table)) {
            $table = explode(',', $table);
        }
        if (!is_array($table)) {
            throw new InvalidArgumentException('table mast be string or array,' . gettype($table) . ' gieven');
        }
        $this->options['table'] = $table;
        return $this;
    }

    public function alias($alias)
    {
        if (is_string($alias)) {
            $alias = explode(',', $alias);
        }
        if (!is_array($alias)) {
            throw new InvalidArgumentException('alias mast be string or array,' . gettype($alias) . ' gieven');
        }
        $this->options['alias'] = $alias;
        return $this;
    }

    public function join(string $table, string $alias, string $on, string $type = null)
    {
        if (is_null($type) || !in_array($type = strtoupper($type), ['left', 'right', 'inner'])) {
            $type = 'LEFT';
        }
        if (empty($this->options['join'])) {
            $this->options['join'] = [];
        }
        $this->options['join'][] = compact('table', 'alias', 'on', 'type');
        return $this;
    }

    public function field(string $field)
    {
        $this->options['field'] = $field;
        return $this;
    }

    /**
     * 数组只实现=
     * 其他语法写原生语句
     * 多次调用逻辑是and
     * @param $whereItem
     * @param array|null $params
     * @return $this
     * @throws InvalidArgumentException
     */
    public function where($whereItem, array $params = null)
    {
        if (empty($this->options['where'])) {
            $this->options['where'] = [
                'string' => [],
                'params' => [],
            ];
        }
        if (is_array($whereItem)) {
            foreach ($whereItem as $key => $val) {
                if (!is_string($val) && !is_numeric($val)) {
                    throw new InvalidArgumentException('alias mast be string or numeric,' . gettype($val) . ' gieven');
                }
                $index = count($this->options['where']['params']);
                $key_index = str_replace('.', '_', $key) . '_' . $index;
                $this->options['where']['string'][] = "$key=:$key_index ";
                $this->options['where']['params'][$key_index] = $val;
            }
        } elseif (is_string($whereItem)) {
            $this->options['where']['string'][] = $whereItem;
            if ($params) {
                $this->options['where']['params'] = array_merge($this->options['where']['params'], $params);
            }
        } else {
            throw new InvalidArgumentException('alias mast be string or array,' . gettype($whereItem) . ' gieven');
        }
        return $this;

    }

    /**
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit(int $limit, int $offset = null)
    {
        if (empty($offset)) {
            $offset = $limit;
            $limit = 0;
        }
        $this->options['limit'] = compact('limit', 'offset');
        return $this;
    }

    /**
     * @param int $page
     * @param int $size
     * @return $this
     */
    public function page(int $page, int $size = null)
    {
        if ($page < 1) {
            $page = 1;
        }
        if (is_null($size)) {
            $size = 20;
        }
        return $this->limit(($page - 1) * $size, $size);
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    protected function parseOptions()
    {
        if(!$this->inited)
        {
            $this->init();
        }
        $options = $this->options;
        $this->options = [];
        //table
        if (empty($options['table'])) {
            if(!empty($this->table))
            {
                $options['table']=[$this->table];
            }
            else{
                throw new InvalidArgumentException('table was miss of options');
            }
        }
        if (!empty($options['alias']) && count($options['alias']) !== count($options['table'])) {
            throw new InvalidArgumentException('count alias and table not exception');
        }
        $prefix = $this->config[0]['prefix'];
        $alisas = empty($options['alias']) ? [] : $options['alias'];
        unset($options['alias']);
        $options['table'] = join(',', array_map(function ($table, $alisa) use ($prefix) {
            return "`$prefix$table` $alisa";
        }, $options['table'], $alisas));

        //join
        $options['join'] = join('', array_map(function ($join) use ($prefix) {
            return "{$join['type']} JOIN `$prefix{$join['table']}` {$join['alias']} on {$join['on']} ";
        }, $options['join'] ?? []));

        //field
        $options['field'] = empty($options['field']) ? '*' : $options['field'];

        //where
        empty($options['params']) && $options['params'] = [];
        if (!empty($options['where'])) {
            $options['params'] = array_merge($options['params'], $options['where']['params']);
            $options['where'] = join(' AND ', $options['where']['string']);
        } else {
            $options['where'] = '1';
        }
        //order
        $options['order'] = empty($options['order']) ? '' : "ORDER BY {$options['order']}";
        //group
        $options['group'] = empty($options['group']) ? '' : "GROUP BY {$options['group']}";
        //having
        $options['having'] = empty($options['having']) ? '' : "HAVING {$options['having']}";

        //limit
        $options['limit'] = empty($options['limit']) ? '' : "LIMIT {$options['limit']['limit']},{$options['limit']['offset']}";

        return $options;
    }

    public function order(string $order)
    {
        $this->options['order'] = $order;
        return $this;
    }

    public function group(string $group)
    {
        $this->options['group'] = $group;
        return $this;
    }

    public function having(string $having)
    {
        $this->options['having'] = $having;
        return $this;
    }

    /**
     * @param array $options
     * @return string
     */
    protected function buildSelectSql(array $options)
    {
        $sql = "SELECT _FIELD_ FROM _TABLE_ _JOIN_ WHERE _WHERE_ _GROUP_ _HAVING_ _ORDER_ _LIMIT_";
        return str_replace([
            '_FIELD_',
            '_TABLE_',
            '_JOIN_',
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
     * @throws Exception
     */
    public function find()
    {
        $this->limit(1);
        if (false === $result = $this->select()) {
            return false;
        }
        if (empty($result)) {
            return [];
        }
        return array_shift($result);
    }

    /**
     * @return array|bool
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function select()
    {
        $options = $this->parseOptions();
        $sql = $this->buildSelectSql($options);
        if (false === $result = $this->query($sql, $options['params'])) {
            return false;
        }
        if (empty($result)) {
            return [];
        }
        if(method_exists($this,'_read_data'))
        {
            foreach($result as $k => $res)
            {
                $result[$k]=$this->_read_data($res,$options);
            }
        }
        return $result;
    }
    protected function _read_data($data,$options){
        return $data;
    }

    /**
     * @param string $field
     * @return array|bool|mixed
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function value(string $field)
    {
        $this->field($field . ' easy_value');
        if (false === $result = $this->find()) {
            return false;
        }
        if (empty($result)) {
            return '';
        }
        return $result['easy_value'];
    }

    /**
     * @param string $field
     * @return array|bool
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function column(string $field)
    {
        $this->field($field . ' easy_column');
        if (false === $result = $this->select()) {
            return false;
        }
        if (empty($result)) {
            return [];
        }
        return array_column($result, 'easy_column');
    }

    /**
     * @param array $options
     * @return string
     */
    protected function buildUpdateSql(array $options)
    {
        $sql = "UPDATE _TABLE_ SET _UPDATE_FIELD_ WHERE _WHERE_ _LIMIT_";
        return str_replace([
            '_TABLE_',
            '_UPDATE_FIELD_',
            '_WHERE_',
            '_LIMIT_',
        ],
            [
                $options['table'],
                $options['update_field'],
                $options['where'],
                $options['limit'],
            ],
            $sql
        );
    }

    /**
     * @param array $update_field
     * @return bool|int
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function save(array $update_field)
    {
        if (empty($this->options['where'])) {
            //没条件返回0条
            return 0;
        }

        $this->options['update_field'] = join(',', array_map(function ($field) {
            return "$field=:" . str_replace('.', '_', $field) . ' ';
        }, array_keys($update_field)));
        $this->options['params'] = $update_field;
        $options = $this->parseOptions();
        $sql = $this->buildUpdateSql($options);
        if (false === $num = $this->execute($sql, $options['params'])) {
            return false;
        }
        return $num;
    }

    /**
     * @param array $add_data
     * @return bool|mixed
     * @throws Exception
     */
    public function add(array $add_data)
    {

        if (false === $this->addAll([$add_data])) {
            return false;
        }
        return $this->initConnect(true)->insert_id;
    }

    /**
     * @param array $data_lists
     * @return bool|int
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function addAll(array $data_lists)
    {
        $this->options['insert_fields'] = [];
        $this->options['insert_values'] = [];
        $index = 0;
        $this->options['params'] = [];
        foreach ($data_lists as $data) {
            $cur_value = [];
            foreach ($data as $field => $value) {
                if (empty($index)) {
                    $this->options['insert_fields'][] = "`$field`";
                }
                $field_key = str_replace('.', '_', $field) . "_$index";
                $cur_value[] = ':' . $field_key . ' ';
                $this->options['params'][$field_key] = $value;
            }
            $this->options['insert_values'][] = '(' . join(',', $cur_value) . ')';
            $index++;
        }
        $this->options['insert_fields'] = join(',', $this->options['insert_fields']);
        $this->options['insert_values'] = join(',', $this->options['insert_values']);
        $options = $this->parseOptions();
        $sql = $this->buildInsertSql($options);
        if (false === $num = $this->execute($sql, $options['params'])) {
            return false;
        }
        return $num;
    }

    protected function buildInsertSql($options)
    {
        $sql = "INSERT INTO _TABLE_ (_INSERT_FIELDS_) VALUES _INSERT_VALUES_";
        return str_replace([
            '_TABLE_',
            '_INSERT_FIELDS_',
            '_INSERT_VALUES_',
        ],
            [
                $options['table'],
                $options['insert_fields'],
                $options['insert_values'],
            ],
            $sql
        );
    }

    /**
     * @return bool|int
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function delete()
    {
        if (empty($this->options['where'])) {
            //没条件返回0条
            return 0;
        }
        $options = $this->parseOptions();
        $sql = $this->buildDeleteSql($options);
        if (false === $num = $this->execute($sql, $options['params'])) {
            return false;
        }
        return $num;
    }

    protected function buildDeleteSql($options)
    {
        $sql = "DELETE FROM _TABLE_ WHERE _WHERE_ _LIMIT_";
        return str_replace([
            '_TABLE_',
            '_WHERE_',
            '_LIMIT_',
        ],
            [
                $options['table'],
                $options['where'],
                $options['limit'],
            ],
            $sql
        );
    }
}