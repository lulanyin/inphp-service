<?php
class App
{
    public static function getContext($name){
        return \Inphp\Service\Context::get($name);
    }

    public static function setContext($name, $value){
        \Inphp\Service\Context::set($name, $value);
    }
}