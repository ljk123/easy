<?php


namespace easy\exception;

use easy\Exception;

class MethodNotFoundException extends Exception
{
    protected $method;

    public function __construct(string $message, string $method=null , $previous = null)
    {
        $this->message = $message;
        $this->method   = $method;

        parent::__construct($message, 0, $previous);
    }

    /**
     * 获取类名
     * @access public
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
}