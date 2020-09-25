<?php


namespace easy\db\swoole;


use easy\db\Interfaces;

class Mysql implements Interfaces
{
    public function query(string $sql, array $params = [])
    {
        // TODO: Implement query() method.
    }

    public function execute(string $sql, array $params = [])
    {
        // TODO: Implement execute() method.
    }

    public function connect(array $config = [])
    {
        // TODO: Implement connect() method.
    }

    public function prepare(string $sql)
    {
        // TODO: Implement prepare() method.
    }

    public function startTrans()
    {
        // TODO: Implement startTrans() method.
    }

    public function rollback()
    {
        // TODO: Implement rollback() method.
    }

    public function commit()
    {
        // TODO: Implement commit() method.
    }
}