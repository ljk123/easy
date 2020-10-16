<?php


namespace easy;


use easy\utils\Str;

abstract class Model
{
    use \easy\traits\Db;
    protected $table;
    protected $data;
    public function __construct(App $app)
    {
        $table = $table??static::class;
        if ( $pos = strrpos($table,'\\') ) {//有命名空间
            $table = substr($table,$pos+1);
        }
        $this->table=Str::snake($table);
    }
    public function hidden($field){
        if(is_string($field))
        {
            $field = explode(',',$field);
        }
        $this->options['hidden']=(array)$field;
        return $this;
    }
    public function append($field){
        if(is_string($field))
        {
            $field = explode(',',$field);
        }
        $this->options['append']=(array)$field;
        return $this;
    }
    protected function _read_data($data,$options)
    {
        foreach ($data as $k=>$v)
        {
            if(method_exists($this,'get'.Str::studly($k).'Attr'))
            {
                $data[$k]=call_user_func([$this,'get'.Str::studly($k).'Attr'],$data[$k],$data);
            }
        }

        if(isset($options['append']))
        {
            foreach ($options['append'] as $k)
            {
                $data[$k]=call_user_func([$this,'get'.Str::studly($k).'Attr'],'',$data);
            }
        }
        if(isset($options['hidden']))
        {
            foreach ($options['hidden'] as $k)
            {
                unset($data[$k]);
            }
        }
        return $data;
    }
}