<?php


namespace easy\db\fpm\mysql;

use PDOStatement;

class Statement
{
    protected $state;
    public function __construct(PDOStatement $state)
    {
        $this->state=$state;
    }
    public function execute(array $params=null){
        if(false===$res=$this->state->execute($params))
        {
            return false;
        }
        return $this->state->fetchAll();
    }
    public function getErrorCode(){
        return $this->state->errorCode();
    }
    public function getErrorInfo(){
        return $this->state->errorInfo();
    }
    public function getError(){
        return [
            'code'=>$this->getErrorCode(),
            'info'=>$this->getErrorInfo(),
        ];
    }
}