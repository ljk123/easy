<?php


namespace easy\exception;

use easy\Exception;

class ClassNotFoundException extends Exception
{
    protected $class;

    public function __construct(string $message, string $class = null, $previous = null)
    {
        $this->message = $message;
        $this->class = $class;

        parent::__construct($message, 0, $previous);
    }

    /**
     * 获取类名
     * @access public
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}