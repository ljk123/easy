<?php


namespace easy;


class Validate
{
    private $rules = [];//所有方法

    protected $rule = [];

    protected $alias = [];

    protected $msgs = [];

    private $error = [];

    public function __construct()
    {
        if (count($this->rules) === 0) {
            $this->setDefaultRules();
        }
    }

    /**
     * @param string $key
     * @param array $call
     * @return $this
     */
    public function addRule(string $key, array $call)
    {
        $this->rules[$key] = $call;
        return $this;
    }

    /**
     * @param array $msgs
     * @return $this
     */
    public function setMessages(array $msgs)
    {
        $this->msgs = $msgs + $this->msgs;
        return $this;
    }

    private $_rule_keys = [
        'stop' => 1
    ];

    /**
     * @param $value
     * @param $rules
     * @param $name
     * @return bool
     * @throws Exception
     */
    private function runRule($value, $rules, $name)
    {
        $arr = explode('|', $rules);
        foreach ($arr as $val) {
            if (isset($this->_rule_keys[$val])) {
                continue;
            }
            $ar = explode(':', $val);
            if (!isset($this->rules[$ar[0]])) {
                throw new Exception('未定义的验证规则:' . $ar[0]);
            }
            if (count($ar) > 1) {
                $args = explode(',', $ar[1]);
            } else {
                $args = [];
            }
            if (isset($this->msgs[$ar[0]])) {
                $msg = $this->msgs[$ar[0]];
            } else {
                $msg = $this->rules[$ar[0]]['msg'];
            }
            $msg = str_replace(':attribute', $name, $msg);
            foreach ($args as $i => $g) {
                $msg = str_replace(':arg' . ($i + 1), $g, $msg);
            }
            array_unshift($args, $value);
            if (!isset($this->rules[$ar[0]])) {
                throw new Exception('未定义的验证规则:' . $ar[0], 5001);
            }
            if ($this->checkOne($this->rules[$ar[0]]['fn'], $args, $msg) === false) {
                return false;
            }
        }
        return true;
    }

    public function rule(array $rule = [])
    {
        $this->rule = $this->rule + $rule;
        return $this;
    }

    /**
     * @param array $arr
     * @param array $rules null 表示验证所有的字段 array 表示筛选/新增出字段验证
     * @param bool $is_append
     * @return $this
     * @throws Exception
     */
    public function validate(array $arr, array $rules = null, $is_append = false)
    {
        if (is_null($rules)) {
            $rules = $this->rule;
        } elseif (is_array($rules)) {
            if ($is_append) {
                $rules = $this->rule + $rules;
            } else {
                $rules = array_map(function ($item) {
                    return $this->rule[$item];
                }, $rules);
            }
        }
        $this->rule = [];
        foreach ($rules as $k => $r) {
            if (isset($arr[$k])) {
                $ret = $this->runRule($arr[$k], $r, isset($this->alias[$k]) ? $this->alias[$k] : $k);
            } else if (strpos($r, 'required') === false) {
                $ret = true;
            } else {
                $ret = $this->runRule(null, $r, isset($this->alias[$k]) ? $this->alias[$k] : $k);
            }
            if ($ret === false && strpos($r, 'stop') !== false) {
                break;
            }
        }
        return $this;
    }

    private function checkOne(\Closure $call, array $args, $msg)
    {
        if ($call->call($this, ...$args) === false) {
            $this->error[] = $msg;
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return string
     */
    public function getError()
    {
        return join(',', $this->error);
    }

    /**
     * @return bool
     */
    public function isOk()
    {
        return count($this->error) === 0;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setAliases(array $arr)
    {
        $this->alias = $arr + $this->alias;
        return $this;
    }

    private function setDefaultRules()
    {
        $this->rules = [
            'required' => [
                'msg' => ':attribute不能为空',
                'fn' => function ($value) {
                    if ($value === '' || $value === null) {
                        return false;
                    } else {
                        return true;
                    }
                }
            ],
            'numeric' => [
                'msg' => ':attribute必须是数字',
                'fn' => function ($value) {
                    return is_numeric($value);
                }
            ],
            'int' => [
                'msg' => ':attribute必须是整数',
                'fn' => function ($value) {
                    return filter_var($value, FILTER_VALIDATE_INT) !== false;
                }
            ],
            'min' => [
                'msg' => ':attribute不能小于:arg1',
                'fn' => function ($value, $arg1) {
                    return $value >= $arg1;
                }
            ],
            'max' => [
                'msg' => ':attribute不能大于:arg1',
                'fn' => function ($value, $arg1) {
                    return $value <= $arg1;
                }
            ],
            'between' => [
                'msg' => ':attribute必须介于:arg1-:arg2之间',
                'fn' => function ($value, $arg1, $arg2) {
                    return $value >= $arg1 && $value <= $arg2;
                }
            ],
            'min_len' => [
                'msg' => ':attribute不能短于:arg1',
                'fn' => function ($value, $arg1) {
                    return strlen($value) >= $arg1;
                }
            ],
            'max_len' => [
                'msg' => ':attribute不能长于:arg1',
                'fn' => function ($value, $arg1) {
                    return strlen($value) <= $arg1;
                }
            ],
            'between_len' => [
                'msg' => ':attribute长度必须介于:arg1-:arg2个字符之间',
                'fn' => function ($value, $arg1, $arg2) {
                    return strlen($value) >= $arg1 && strlen($value) <= $arg2;
                }
            ],
            'uint' => [
                'msg' => ':attribute必须为大于0的正整数',
                'fn' => function ($value) {
                    $v = filter_var($value, FILTER_VALIDATE_INT);
                    return $v && $v > 0;
                }
            ],
            'email' => [
                'msg' => ':attribute格式不正确',
                'fn' => function ($value) {
                    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                }
            ],
            'ip' => [
                'msg' => ':attribute格式不正确',
                'fn' => function ($value) {
                    return filter_var($value, FILTER_VALIDATE_IP) !== false;
                }
            ]
        ];
        if (method_exists($this, 'rules')) {
            $this->rules = $this->rules + call_user_func([$this, 'rules']);
        }

    }
}