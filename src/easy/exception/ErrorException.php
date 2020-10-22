<?php


namespace easy\exception;

use easy\Exception;

class ErrorException extends Exception
{

    /**
     * 用于保存错误级别
     * @var integer
     */
    protected $severity;

    public function __construct(string $message, int $severity = null, string $file = null, int $line = null)
    {
        $this->severity = (int)$severity;
        $this->message = $message;
        $this->file = (string)$file;
        $this->line = (int)$line;
        $this->code = 0;
    }

    /**
     * 获取错误级别
     * @access public
     * @return integer 错误级别
     */
    final public function getSeverity()
    {
        return $this->severity;
    }
}