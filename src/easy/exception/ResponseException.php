<?php


namespace easy\exception;


use easy\Exception;

/**
 * Class ResponseException
 * 抛出该异常可以结束控制器执行 主要用于在构造方法拦截
 * @package easy\exception
 */
class ResponseException extends Exception
{
    private $data;
    public function __construct($data=[])
    {
        $this->data=$data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}