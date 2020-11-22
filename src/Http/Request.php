<?php
namespace Small\Service\Http;

use Small\Service\IRequest;

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
        $client = Container::getClient();
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
        $client = Container::getClient();
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
        $client = Container::getClient();
        $client->cookie = $client->cookie ?? [];
        if(!isset($client->cookie[$name]) || !isset($client->cookie[$name."_hash"])){
            return $default;
        }
        $value = $client->cookie[$name];
        $hash = $client->cookie[$name."_hash"];
        $config = Container::getConfig();
        $key = $config['cookie']['hash_key'] ?? '1a2b3c5g';
        if(hash_hmac("sha1", $value, $key) == $hash){
            return $value;
        }
        return $default;
    }

    /**
     * 保存 Cookie
     * @param string $name
     * @param $value
     * @param int $time
     */
    public static function setCookie(string $name, $value, $time = 3600){
        $config = Container::getConfig();
        //混淆加密字符
        $key = $config['cookie']['hash_key'] ?? '1a2b3c5g';
        //加密值
        $hash = hash_hmac("sha1", $value, $key);
        //跨域共享域名
        $domains = $config['cookie']['domains'] ?? [];
        $domains = is_array($domains) ? $domains : [];
        //保存位置
        $path = $config['cookie']['path'] ?? "/";
        //获取客户端
        $client = Container::getClient();
        $client->cookie = $client->cookie ?? [];
        $client->cookie[$name] = $value;
        $client->cookie[$name."_hash"] = $hash;
        //得到当前请求的域名
        $domains[] = $client->host;
        //获取对象
        $response = Container::getResponse();
        //每个域名都保存一次
        foreach ($domains as $domain){
            if(Container::isSwoole()){
                $response->cookie($name, $value, time() + $time, $path, $domain, $config['cookie']['secure'], $config['cookie']['http_only']);
                $response->cookie($name."_hash", $hash, time() + $time, $path, $domain, $config['cookie']['secure'], $config['cookie']['http_only']);
            }else{
                setcookie($name, $value, time() + $time, $path, $domain, $config['cookie']['secure'], $config['cookie']['http_only']);
                setcookie($name."_hash", $hash, time() + $time, $path, $domain, $config['cookie']['secure'], $config['cookie']['http_only']);
            }
        }
        Container::setResponse($response);
    }

    /**
     * 移除cookie
     * @param string $name
     */
    public static function dropCookie(string $name){
        self::setCookie($name, null, -1);
    }

}