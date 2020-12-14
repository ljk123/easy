<?php


namespace easy\traits;

use easy\App;
use easy\Cache;
use easy\Container;
use easy\Db;
use easy\Exception;
use easy\exception\InvalidArgumentException;

/**
 * Class Chains
 * 提供 简单链式操作
 * @package easy
 */
trait Chains
{
    private $inited = false;
    protected $sql = "";

    protected function init()
    {
        if ($this->inited) {
            return;
        }
        if ($this instanceof Db) {
            $this->db = $this;
        } else {
            /**@var App $app */
            $app = Container::getInstance()->get('app');
            $this->db = $app->db;
        }
        $this->inited = true;
    }

    protected $error = '';

    public function getError()
    {
        return $this->error;
    }


    /**@var Db $db */
    protected $db;

    //  只提供简单的链式 复杂的自己走sql
    protected $options = [];

    /**
     * @param $table
     * @return $this
     */
    public function table($table)
    {
        if (is_string($table)) {
            $table = explode(',', $table);
        }
        if (!is_array($table)) {
            return $this;
        }
        $this->options['table'] = $table;
        return $this;
    }

    /**
     * @param $alias
     * @return $this
     */
    public function alias($alias)
    {
        if (is_string($alias)) {
            $alias = explode(',', $alias);
        }
        if (!is_array($alias)) {
            return $this;
        }
        $this->options['alias'] = $alias;
        return $this;
    }

    public function join(string $table, string $alias, string $on, string $type = null)
    {
        if (is_null($type) || !in_array($type = strtoupper($type), ['LEFT', 'RIGHT', 'INNER'])) {
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
                if (!is_string($val) && !is_numeric($val) && !is_array($val)) {
                    throw new InvalidArgumentException('where key :' . $key . ' must be string or numeric or array,' . gettype($val) . ' gieven');
                }
                $index = count($this->options['where']['params']);
                $key_index = str_replace('.', '_', $key) . '_' . $index;
                if (!is_array($val)) {
                    $val = ['eq', $val];
                }
                switch ($val[0]) {
                    case 'eq':
                        $this->options['where']['string'][] = "$key=:$key_index ";
                        $this->options['where']['params'][$key_index] = $val[1];
                        break;
                    case 'neq':
                        $this->options['where']['string'][] = "$key!=:$key_index ";
                        $this->options['where']['params'][$key_index] = $val[1];
                        break;
                    case 'in':
                        if (!empty($val[1])) {
                            if (is_string($val[1])) {
                                $val[1] = explode(',', $val[1]);
                            }
                            $this->options['where']['string'][] = "$key in ( " . join(',', array_map(function ($k) use ($key_index) {
                                    return ':' . $key_index . '_' . $k;
                                }, array_keys($val[1]))) . " )";
                            foreach ($val[1] as $k => $item) {
                                $this->options['where']['params'][$key_index . '_' . $k] = $item;
                            }
                        }
                        break;
                }
            }
        } elseif (is_string($whereItem)) {
            $this->options['where']['string'][] = $whereItem;
            if ($params) {
                $this->options['where']['params'] = array_merge($this->options['where']['params'], $params);
            }
        } else {
            throw new InvalidArgumentException('where must be string or array,' . gettype($whereItem) . ' gieven');
        }
        return $this;

    }

    /**
     * @param int $offset
     * @param int $size
     * @return $this
     */
    public function limit(int $offset, int $size = null)
    {
        if (empty($size)) {
            $size = $offset;
            $offset = 0;
        }
        $this->options['limit'] = compact('offset', 'size');
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
        $options = $this->options;
        $this->options = [];
        //table
        if (empty($options['table'])) {
            if (!empty($this->table)) {
                $options['table'] = [$this->table];
            } else {
                throw new InvalidArgumentException('table was miss of options');
            }
        }
        if (!empty($options['alias']) && count($options['alias']) !== count($options['table'])) {
            throw new InvalidArgumentException('count alias and table not exception');
        }
        $prefix = $this->db->getPrefix();
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
        $options['limit'] = empty($options['limit']) ? '' : "LIMIT {$options['limit']['offset']},{$options['limit']['size']}";

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
     */
    public function select()
    {
        try {
            $options = $this->parseOptions();
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        $sql = $this->buildSelectSql($options);
        if (isset($options['fetch_sql']) && $options['fetch_sql']) {
            $params = [];
            foreach ($options['params'] as $key => $param) {
                $params[$this->table . '_' . $key] = $param;
            }
            $sql = str_replace(array_keys($options['params']), array_keys($params), $sql);
            return ['sql' => $sql, 'params' => $params];
        }
        if (isset($options['cache'])) {
            if (is_null($options['cache']['expire']) && is_int($options['cache']['key'])) {
                $key = md5(serialize($options));
                $expire = $options['cache']['key'];
            } else {
                $key = $options['cache']['key'];
                $expire = $options['cache']['expire'];
            }
            if ($result = Container::getInstance()->get('cache')->get($key)) {
                return $result;
            }
            $cache = compact('key', 'expire');
        }
        if (false === $result = $this->db->query($sql, $options['params'])) {
            return false;
        }
        $this->sql = $this->escape($sql, $options['params']);
        if (empty($result)) {
            $result = [];
        } else {
            foreach ($result as $k => $res) {
                $result[$k] = $this->_read_data($res, $options);
            }
        }
        if (isset($cache)) {
            Container::getInstance()->get('cache')->set($key, $result, $expire);
        }
        return $result;
    }

    protected function _read_data($data, $options)
    {
        return $data;
    }

    protected function _write_data($data, $options)
    {
        return $data;
    }

    /**
     * @param string $field
     * @return array|bool|mixed
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
     */
    public function save(array $update_field)
    {
        if (empty($this->options['where'])) {
            //没条件返回0条
            return 0;
        }
        try {
            $options = $this->parseOptions();
            $update_field = $this->_write_data($update_field, $options);
            $join = [];
            $params = [];
            foreach ($update_field as $field => $value) {
                if (is_array($value)) {
                    if ($value[0] === 'exp') {
                        $join[] = "`$field`=$value[1]";
                    }
                } elseif (is_string($value) || is_numeric($value)) {
                    $join[] = "`$field`=:" . str_replace('.', '_', $field) . ' ';
                    $params[$field] = $value;
                }
            }
            $options['update_field'] = join(',', $join);
            $options['params'] += $params;
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        $sql = $this->buildUpdateSql($options);
        if (false === $num = $this->db->execute($sql, $options['params'])) {
            $this->error = $this->db->getError();
            return false;
        }
        $this->sql = $this->escape($sql, $options['params']);
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
        return $this->db->initConnect(true)->insert_id;
    }

    protected function lazyWrite($guid, $step, $lazyTime)
    {
        /**@var Cache $cache */
        $cache = Container::getInstance()->get('cache');
        if (!is_null($value = $cache->get($guid))) { // 存在缓存写入数据
            if (time() > (int)($cache->get($guid . '_time')) + $lazyTime) {
                // 延时更新时间到了，删除缓存数据 并实际写入数据库
                $cache->set($guid, null);
                $cache->set($guid . '_time', null);
                return $value + $step;
            } else {
                // 追加数据到缓存
                $cache->set($guid, $value + $step);
                return false;
            }
        } else { // 没有缓存数据
            $cache->set($guid, $step);
            // 计时开始
            $cache->set($guid . '_time', time());
            return false;
        }
    }

    public function setInc(string $field, int $step = 1, int $delay = 0)
    {
        if ($delay > 0) {// 延迟写入
            $condition = $this->options['where'];
            $guid = md5($this->table ?? $this->options['table'] . '_' . $field . '_' . serialize($condition));
            $step = $this->lazyWrite($guid, $step, $delay);
            if (empty($step)) {
                return true; // 等待下次写入
            }
        }
        return $this->save([$field => ['exp', "`$field` + $step"]]);
    }

    public function setDec(string $field, int $step = 1, int $delay = 0)
    {
        return $this->setInc($field, -$step, $delay);
    }

    /**
     * @param array $data_lists
     * @return bool|int
     */
    public function addAll(array $data_lists)
    {

        try {
            $options = $this->parseOptions();

            $options['insert_fields'] = [];
            $options['insert_values'] = [];
            $index = 0;
            $options['params'] = [];
            foreach ($data_lists as $data) {
                $data = $this->_write_data($data, $options);
                $cur_value = [];
                foreach ($data as $field => $value) {
                    if (empty($index)) {
                        $options['insert_fields'][] = "`$field`";
                    }
                    $field_key = str_replace('.', '_', $field) . "_$index";
                    $cur_value[] = ':' . $field_key . ' ';
                    $options['params'][$field_key] = $value;
                }
                $options['insert_values'][] = '(' . join(',', $cur_value) . ')';
                $index++;
            }
            $options['insert_fields'] = join(',', $options['insert_fields']);
            $options['insert_values'] = join(',', $options['insert_values']);

        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        $sql = $this->buildInsertSql($options);
        if (false === $num = $this->db->execute($sql, $options['params'])) {
            $this->error = $this->db->getError();
            return false;
        }
        $this->sql = $this->escape($sql, $options['params']);
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
     */
    public function delete()
    {
        if (empty($this->options['where'])) {
            //没条件返回0条
            return 0;
        }
        try {
            $options = $this->parseOptions();
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        $sql = $this->buildDeleteSql($options);
        if (false === $num = $this->db->execute($sql, $options['params'])) {
            $this->error = $this->db->getError();
            return false;
        }
        $this->sql = $this->escape($sql, $options['params']);
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

    protected function escape(string $sql, array $params = [])
    {
        return str_replace(array_map(function ($field) {
            return ':' . $field;
        }, array_keys($params)), array_map(function ($value) {
            return is_string($value) ? '\'' . $value . '\'' : $value;
        }, $params), $sql);
    }

    public function getLastSql()
    {
        return $this->sql;
    }

    public function cache($key, $expire = null)
    {
        $this->options['cache'] = compact('key', 'expire');
        return $this;
    }

    public function fetchSql($fetch_sql = true)
    {
        $this->options['fetch_sql'] = $fetch_sql;
        return $this;
    }
}