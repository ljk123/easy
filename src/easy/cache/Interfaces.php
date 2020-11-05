<?php

namespace easy\cache;

interface Interfaces
{
    public function get(string $key);

    public function set(string $key, $value, int $expire = 0);
}