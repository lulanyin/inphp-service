<?php
namespace Inphp\Service;

use Inphp\Service\Object\Client;
use Inphp\Service\Object\Status;
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
     * @param int $client_id
     * @return mixed|null
     */
    public static function get(string $name, $client_id = 0){
        if(self::isSwoole()){
            $client_id = $client_id!=0 ? $client_id : self::getClientId();
            if($client_id > 0){
                //从缓存中读取
                $cache_key = "client_{$client_id}";
                $data = Cache::get($cache_key);
                $data = is_array($data) ? $data : [];
                if(!empty($data)){
                    return $data[$name] ?? null;
                }
                return null;
            }
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
     * @param int $client_id
     */
    public static function set(string $name, $value, $client_id = 0){
        if(self::isSwoole()){
            $client_id = $client_id != 0 ? $client_id : self::getClientId();
            if($client_id > 0){
                //从缓存中读取出来，并修改数据，再重新保存
                $cache_key = "client_{$client_id}";
                $data = Cache::get($cache_key);
                $data = is_array($data) ? $data : [];
                $data[$name] = $value;
                Cache::set($cache_key, $data);
            }else{
                //常规的保存在协和上下文即可，协程退出时自动清理
                Coroutine::getContext()[$name] = $value;
            }
        }else{
            self::$contexts[$name] = $value;
        }
    }

    /**
     * 清除客户端所有数据
     * @param int $client_id
     */
    public static function clean($client_id = 0){
        $client_id = $client_id != 0 ? $client_id : self::getClientId();
        if(self::isSwoole() && $client_id > 0){
            $cache_key = "client_{$client_id}";
            Cache::remove($cache_key);
        }
    }

    /**
     * 保存当前客户端编号到上下文
     * @param $client_id
     */
    public static function setClientId($client_id){
        if($client_id > 0){
            if(self::isSwoole()){
                //保存到当前所处理的协程的上下文，方便后续使用
                Coroutine::getContext()['client_id'] = $client_id;
            }else{
                //似乎用不到
                self::$contexts['client_id'] = $client_id;
            }
        }
    }

    /**
     * 获取当前处理的客户端编号
     * @return int
     */
    public static function getClientId(){
        if(self::isSwoole()){
            $client_id = Coroutine::getContext()['client_id'];
            $client_id = is_numeric($client_id) && $client_id > 0 ? $client_id : 0;
            return $client_id;
        }
        return self::$contexts['client_id'] ?? 0;
    }

    /**
     * 从上下文中获取当前的客户端数据
     * @param int $client_id
     * @return Client
     */
    public static function getClient($client_id = 0){
        $client = self::get('client', $client_id);
        if(is_array($client)){
            $client = new Client($client);
        }
        return $client;
    }

    /**
     * 设置当前客户端数据到上下文
     * @param Client $client
     * @param int $client_id
     */
    public static function setClient(Client $client, $client_id = 0){
        self::set('client', $client, $client_id);
    }

    /**
     * 移除客户端数据，仅在缓存时使用
     * @param $client_id
     */
    public static function removeClient($client_id = 0){
        self::set('client', null, $client_id);
    }

    /**
     * 获取 request 对象
     * @return mixed|null
     */
    public static function getRequest(){
        return self::get('request', -1);
    }

    /**
     * 保存 request 对象到上下文
     * @param $request
     */
    public static function setRequest($request){
        self::set('request', $request, -1);
    }

    /**
     * 获取 response 对象
     * @return mixed|null
     */
    public static function getResponse(){
        return self::get('response', -1);
    }

    /**
     * 保存response对象到上下文
     * @param $response
     */
    public static function setResponse($response){
        self::set('response', $response, -1);
    }

    /**
     * 获取server对象
     * @return \Swoole\WebSocket\Server|\Swoole\Http\Server
     */
    public static function getServer(){
        if(self::isSwoole()){
            //保存到当前所处理的协程的上下文，方便后续使用
            return Coroutine::getContext()['server'];
        }else{
            //似乎用不到
            return self::$contexts['server'] ?? null;
        }
    }

    /**
     * 设置 server 对象
     * @param \Swoole\WebSocket\Server|\Swoole\Http\Server $server
     */
    public static function setServer($server){
        if(self::isSwoole()){
            //保存到当前所处理的协程的上下文，方便后续使用
            Coroutine::getContext()['server'] = $server;
        }
    }

    /**
     * 获取路由状态
     * @return Status|null
     */
    public static function getStatus(){
        if(self::isSwoole()){
            //保存到当前所处理的协程的上下文，方便后续使用
            return Coroutine::getContext()['status'] ?? null;
        }else{
            return self::$contexts['status'] ?? null;
        }
    }

    /**
     * 保存路由状态
     * @param Status $status
     */
    public static function setStatus(Status $status){
        if(self::isSwoole()){
            //保存到当前所处理的协程的上下文，方便后续使用
            Coroutine::getContext()['status'] = $status;
        }else{
            self::$contexts['status'] = $status;
        }
    }

    /**
     * 判断是否是 Swoole 服务
     * @return bool
     */
    public static function isSwoole(){
        return defined("INPHP_SERVICE_PROVIDER") && INPHP_SERVICE_PROVIDER == SWOOLE;
    }
}