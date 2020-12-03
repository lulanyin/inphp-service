<?php
// +----------------------------------------------------------------------
// | INPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2020 https://inphp.cc All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/MIT )
// +----------------------------------------------------------------------
// | Author: lulanyin <me@lanyin.lu>
// +----------------------------------------------------------------------
namespace Inphp\Service\Http;

use Inphp\Service\Config;
use Inphp\Service\Context;

class Request
{
    /**
     * 获取地址参数值
     * @param string $name
     * @param null $default
     * @return mixed|null
     */
    public static function get(string $name, $default = null)
    {
        // TODO: Implement get() method.
        $client = Context::getClient();
        $client->get = $client->get ?? [];
        $client->get[$name] = $client->get[$name] ?? $default;
        return $client->get[$name];
    }

    /**
     * 获取POST数据
     * @param string $name
     * @param null $default
     * @return mixed|null
     */
    public static function post(string $name, $default = null)
    {
        // TODO: Implement post() method.
        $client = Context::getClient();
        $client->post = $client->post ?? [];
        $client->post[$name] = $client->post[$name] ?? $default;
        return $client->post[$name];
    }

    /**
     * 获取客户端提交的数据，包括GET,POST，优先POST
     * @param string $name
     * @param null $default
     * @return mixed|null
     */
    public static function request(string $name, $default = null)
    {
        // TODO: Implement request() method.
        return self::post($name, self::get($name, $default));
    }

    /**
     * 获取 Cookie
     * @param string $name
     * @param null $default
     * @return string|null
     */
    public static function getCookie(string $name, $default = null){
        $client = Context::getClient();
        $client->cookie = $client->cookie ?? [];
        if(!isset($client->cookie[$name]) || !isset($client->cookie[$name."_hash"])){
            return $default;
        }
        $value = $client->cookie[$name];
        $hash = $client->cookie[$name."_hash"];
        $config = Config::get('http');
        $key = $config['cookie']['hash_key'] ?? '123456';
        if(hash_hmac("sha1", $value, $key) == $hash){
            return $value;
        }
        return $default;
    }

    /**
     * 保存 Cookie
     * @param string $name
     * @param string $value
     * @param int $time
     */
    public static function setCookie(string $name, string $value, $time = 3600){
        //获取对象
        $response = Context::getResponse();
        $response->withCookie($name, $value, $time);
    }

    /**
     * 移除cookie
     * @param string $name
     */
    public static function dropCookie(string $name){
        self::setCookie($name, null, -1);
    }
}