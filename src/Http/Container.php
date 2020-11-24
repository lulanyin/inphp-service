<?php
namespace Inphp\Service\Http;

use Inphp\Service\Object\Client;
use Swoole\Coroutine;

/**
 * 临时对象保存
 * Class Container
 * @package Inphp\Service\Http
 */
class Container
{
    /**
     * 临时对象
     * @var array
     */
    public static $containers = [];

    /**
     * 配置
     * @var array
     */
    public static $config = [];

    /**
     * 获取对象
     * @param string $name
     * @return mixed|null
     */
    public static function get(string $name){
        if(self::isSwoole()){
            $context = Coroutine::getContext();
            //assert($context instanceof Coroutine\Context);
            return $context[$name] ?? null;
            /*
            $coroutine_id = Coroutine::getCid();
            $containers = self::$containers[$coroutine_id] ?? [];
            return $containers[$name] ?? null;
            */
        }else{
            return self::$containers[$name] ?? null;
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
            /*
            $coroutine_id = Coroutine::getCid();
            self::$containers[$coroutine_id] = self::$containers[$coroutine_id] ?? [];
            self::$containers[$coroutine_id][$name] = $value;
            */
        }else{
            self::$containers[$name] = $value;
        }
    }

    /**
     * 获取临时 Request
     * @return \Swoole\Http\Request
     */
    public static function getRequest(){
        return self::get("request");
    }

    /**
     * 保存临时 Request
     * @param $request
     */
    public static function setRequest($request){
        self::set("request", $request);
    }

    /**
     * 获取临时 Response
     * @return Response
     */
    public static function getResponse(){
        return self::get("response");
    }

    /**
     * 保存临时 Response
     * @param $response
     */
    public static function setResponse($response){
        self::set("response", $response);
    }

    /**
     * 获取配置
     * @return array|null
     */
    public static function getConfig(){
        if(!empty(self::$config)){
            return self::$config;
        }
        if(defined("SERVICE_CONFIG")){
            if(is_array(SERVICE_CONFIG)){
                self::$config = SERVICE_CONFIG;
            }elseif(is_file(SERVICE_CONFIG)){
                self::$config = require SERVICE_CONFIG;
            }
            return self::$config;
        }
        return null;
    }

    /**
     * 获取当前客户端数据
     * @return Client
     */
    public static function getClient(){
        return self::get("client");
    }

    /**
     * 保存当前客户端数据
     * @param Client $client
     */
    public static function setClient(Client $client){
        self::set("client", $client);
    }

    /**
     * 更新客户端某个数据
     * @param $name
     * @param $value
     */
    public static function updateClient($name, $value){
        $client = self::getClient();
        if(property_exists($client, $name)){
            $client->{$name} = $value;
        }
    }

    /**
     * 判断是否是 Swoole 服务
     * @return bool
     */
    public static function isSwoole(){
        return defined("SERVICE_PROVIDER") && SERVICE_PROVIDER == SWOOLE;
    }
}