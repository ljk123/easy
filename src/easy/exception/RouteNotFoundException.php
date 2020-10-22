<?php


namespace easy\exception;

use easy\Exception;

class RouteNotFoundException extends Exception
{
    protected $class;
    /**
     * @var string
     */
    private $path;

    public function __construct(string $message, string $path = null, $previous = null)
    {
        $this->message = $message;
        $this->path = $path;

        parent::__construct($message, 0, $previous);
    }

    /**
     * 获取类名
     * @access public
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}