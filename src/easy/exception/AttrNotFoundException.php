<?php


namespace easy\exception;

use easy\Exception;

class AttrNotFoundException extends Exception
{
    protected $attr;

    public function __construct(string $message, string $attr = null, $previous = null)
    {
        $this->message = $message;
        $this->attr = $attr;

        parent::__construct($message, 0, $previous);
    }

    /**
     * 获取类名
     * @access public
     * @return string
     */
    public function getAttr()
    {
        return $this->attr;
    }
}