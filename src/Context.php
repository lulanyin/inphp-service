<?php
namespace Inphp\Service;

use Inphp\Service\Object\Client;
use Swoole\Coroutine;

class Context
{
    /**
     * php-fpm 使用
     * @var array
     */
    public static $contexts = [];

    /**
     * 获取对象
     * @param string $name
     * @return mixed|null
     */
    public static function get(string $name){
        if(self::isSwoole()){
            $context = Coroutine::getContext();
            return $context[$name] ?? null;
        }else{
            return self::$contexts[$name] ?? null;
        }
    }

    /**
     * 保存对象
     * @param string $name
     * @param $value
     */
    public static function set(string $name, $value){
        if(self::isSwoole()){
            Coroutine::getContext()[$name] = $value;
        }else{
            self::$contexts[$name] = $value;
        }
    }

    /**
     * 从上下文中获取当前的客户端数据
     * @return Client
     */
    public static function getClient(){
        return self::get('client');
    }

    /**
     * 设置当前客户端数据到上下文
     * @param Client $client
     */
    public static function setClient(Client $client){
        self::setClient($client);
    }

    /**
     * 判断是否是 Swoole 服务
     * @return bool
     */
    public static function isSwoole(){
        return defined("SERVICE_PROVIDER") && SERVICE_PROVIDER == SWOOLE;
    }
}