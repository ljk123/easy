<?php


namespace easy\redis\swoole;


use easy\redis\Interfaces;

/**
 * @method  get
 * @method  set(string $key, string $value)
 * @method  setex
 * @method  setnx
 * @method  delete
 * @method  exists
 * @method  incr
 * @method  getMultiple
 * @method  lpush
 * @method  rpush
 * @method  lpop
 * @method  lsize
 * @method  len
 * @method  lget
 * @method  lset
 * @method  lgetrange
 * @method  lremove
 * @method  sadd
 * @method  sremove
 * @method  smove
 * @method  scontains
 * @method  ssize
 * @method  spop
 * @method  sinter
 * @method  sinterstore
 * @method  sunion
 * @method  sunionstore
 * @method  sdiff
 * @method  sdiffstore
 * @method  smembers
 * @method  sgetmembers
 */
class Redis implements Interfaces
{

    public function connect(array $config = [])
    {
        // TODO: Implement connect() method.
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement @method  get
        // TODO: Implement @method  set(string $key,string $value)
        // TODO: Implement @method  setex
        // TODO: Implement @method  setnx
        // TODO: Implement @method  delete
        // TODO: Implement @method  exists
        // TODO: Implement @method  incr
        // TODO: Implement @method  getMultiple
        // TODO: Implement @method  lpush
        // TODO: Implement @method  rpush
        // TODO: Implement @method  lpop
        // TODO: Implement @method  lsize
        // TODO: Implement @method  len
        // TODO: Implement @method  lget
        // TODO: Implement @method  lset
        // TODO: Implement @method  lgetrange
        // TODO: Implement @method  lremove
        // TODO: Implement @method  sadd
        // TODO: Implement @method  sremove
        // TODO: Implement @method  smove
        // TODO: Implement @method  scontains
        // TODO: Implement @method  ssize
        // TODO: Implement @method  spop
        // TODO: Implement @method  sinter
        // TODO: Implement @method  sinterstore
        // TODO: Implement @method  sunion
        // TODO: Implement @method  sunionstore
        // TODO: Implement @method  sdiff
        // TODO: Implement @method  sdiffstore
        // TODO: Implement @method  smembers
        // TODO: Implement @method  sgetmembers
    }
}