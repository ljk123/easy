<?php


namespace easy\app;


use easy\App;

interface Container
{
    public static function __make(App $app);
}