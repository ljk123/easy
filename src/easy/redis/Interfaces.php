<?php

namespace easy\redis;

/**
 * Interface Interfaces
 * @method get
 * @method set(string $key, string $value)
 * @method setex
 * @method setnx
 * @method del
 * @method exists
 * @method incr
 * @method getMultiple
 * @method lpush
 * @method rpush
 * @method lpop
 * @method lsize
 * @method len
 * @method lget
 * @method lset
 * @method lgetrange
 * @method lremove
 * @method sadd
 * @method sremove
 * @method smove
 * @method scontains
 * @method ssize
 * @method spop
 * @method sinter
 * @method sinterstore
 * @method sunion
 * @method sunionstore
 * @method sdiff
 * @method sdiffstore
 * @method smembers
 * @method sgetmembers
 * @package easy\redis
 */
interface Interfaces
{
    public function connect(array $config = []);
}