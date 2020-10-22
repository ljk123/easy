<?php


namespace easy\db;


interface Interfaces
{
    public function query(string $sql, array $params = []);

    public function execute(string $sql, array $params = []);

    public function connect(array $config = []);

    public function prepare(string $sql);

    //事务
    public function startTrans();

    public function rollback();

    public function commit();

}