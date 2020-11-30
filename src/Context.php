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
     * @param int $client_id
     * @return Client
     */
    public static function getClient($client_id = 0){
        return self::get('client');
    }

    /**
     * 设置当前客户端数据到上下文
     * @param Client $client
     * @param int $client_id
     */
    public static function setClient(Client $client, $client_id = 0){
        self::set('client', $client);
    }

    /**
     * 获取 request 对象
     * @return mixed|null
     */
    public static function getRequest(){
        return self::get('request');
    }

    /**
     * 保存 request 对象到上下文
     * @param $request
     */
    public static function setRequest($request){
        self::set('request', $request);
    }

    /**
     * 获取 response 对象
     * @return mixed|null
     */
    public static function getResponse(){
        return self::get('response');
    }

    /**
     * 保存response对象到上下文
     * @param $response
     */
    public static function setResponse($response){
        self::set('response', $response);
    }

    /**
     * 判断是否是 Swoole 服务
     * @return bool
     */
    public static function isSwoole(){
        return defined("INPHP_SERVICE_PROVIDER") && INPHP_SERVICE_PROVIDER == SWOOLE;
    }
}