<?php


namespace easy\exception;

use easy\Exception;

class DbException extends Exception
{
    protected $config;

    /**
     * DbException constructor.
     * @param string $message
     * @param array $config
     * @param null $previous
     */
    public function __construct($message,$config, $previous = null)
    {
        $this->message = $message;
        unset($config['database']);
        unset($config['username']);
        unset($config['password']);
        $this->config=$config;
        parent::__construct($message, 0, $previous);
    }
    public function getConfig(){
        return $this->config;
    }
}