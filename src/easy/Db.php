<?php


namespace easy;

use easy\traits\Db as DbTrait;
use easy\traits\Singleton;

/**
 * Class Db
 * 提供 query execute 方法 简单链式操作
 * @package easy
 */

class Db
{
    use Singleton,DbTrait;
    private function __clone()
    {

    }
    private function __construct()
    {
    }
}