<?php

use Inphp\Service\Context;
use Inphp\Service\IService;
use Inphp\Service\Service;

class App
{
    public static $server = null;

    public static function getContext($name){
        return Context::get($name);
    }

    public static function setContext($name, $value){
        Context::set($name, $value);
    }

    /**
     * @param bool $swoole
     * @param bool $ws
     * @return IService
     */
    public static function init($swoole = false, $ws = false){
        $service = new Service($swoole, $ws);
        self::$server = $service->server;
        return $service;
    }
}