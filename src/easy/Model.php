<?php


namespace easy;


use easy\traits\Chains;
use easy\utils\Str;

abstract class Model
{
    use Chains;
    protected $table;
    protected $data;
    /**@var Validate $validate */
    protected $validate;

    public function __construct($validate = null)
    {
        $table = $table ?? static::class;
        if ($pos = strrpos($table, '\\')) {//有命名空间
            $table = substr($table, $pos + 1);
        }
        $this->table = Str::snake($table);
        $this->init();
        if (empty($validate) || !$validate instanceof Validate) {
            try {
                $refl = new \ReflectionClass(static::class);
                $doc_coment = $refl->getDocComment();
                $doc = explode(PHP_EOL, $doc_coment);
                foreach ($doc as $line) {
                    if (false !== $pos = strpos($line, '@validate')) {
                        $vaild = trim(substr($line, $pos + strlen('@validate')));
                        if (class_exists($vaild)) {
                            $validate = new $vaild;
                        }
                    }
                }
            } catch (\ReflectionException $e) {

            }
        }
        $this->validate = $validate;

    }

    public function hidden($field)
    {
        if (is_string($field)) {
            $field = explode(',', $field);
        }
        $this->options['hidden'] = (array)$field;
        return $this;
    }

    public function append($field)
    {
        if (is_string($field)) {
            $field = explode(',', $field);
        }
        $this->options['append'] = (array)$field;
        return $this;
    }

    protected function _read_data($data, $options)
    {
        foreach ($data as $k => $v) {
            if (method_exists($this, 'get' . Str::studly($k) . 'Attr')) {
                $data[$k] = call_user_func([$this, 'get' . Str::studly($k) . 'Attr'], $data[$k], $data);
            }
        }

        if (isset($options['append'])) {
            foreach ($options['append'] as $k) {
                $data[$k] = method_exists($this, 'get' . Str::studly($k) . 'Attr') ? call_user_func([$this, 'get' . Str::studly($k) . 'Attr'], '', $data) : null;
            }
        }
        if (isset($options['hidden'])) {
            foreach ($options['hidden'] as $k) {
                unset($data[$k]);
            }
        }
        return $data;
    }

    public function validate(array $data, array $rules = null, Validate $validate = null)
    {
        if (empty($this->validate) && empty($validate)) {
            $this->error = "未找到验证器";
            return false;
        }
        if ($validate) {
            $this->validate = $validate;
        }
        try {
            if ($this->validate->validate($data, $rules)->isOk()) {
                return true;
            }
            $this->error = $this->validate->getError();
            return false;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
}