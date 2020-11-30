<?php
namespace Inphp\Service\Http;

use Inphp\Service\Config;
use Inphp\Service\Context;
use Inphp\Service\Middleware\ISessionMiddleware;

class Session
{

    /**
     * 获取 session
     * @param string|null $name
     * @param null $default
     * @return array|mixed|null
     */
    public static function get(string $name = null, $default = null){
        if(Context::isSwoole()) {
            $php_session_id = Request::getCookie("PHP_SESSION_ID");
            if(!empty($php_session_id)){
                //获取
                $config = Config::get('http');
                $session_set = $config["session"];
                switch($session_set['driver']){
                    case "middleware":
                        //使用中间键处理
                        self::processMiddleware('get', $name, $default);
                        break;
                    default :
                        $file_path = $session_set['file_path'];
                        $file_path = substr($file_path, -1) == '/' ? $file_path : "{$file_path}/";
                        //保存到文件
                        $file = $file_path.$php_session_id.".txt";
                        if(file_exists($file)){
                            $data = file_get_contents($file);
                            $data = !empty($data) ? @json_decode($data, true) : [];
                            if(is_null($name)){
                                $list = [];
                                foreach ($data as $key=>$d){
                                    $list[$key] = $d['value'];
                                }
                                return $list;
                            }
                            $value = $data[$name] ?? [];
                            return $value['value'] ?? $default;
                        }
                        break;
                }
            }
            return $default;
        }else{
            if(session_status() !== PHP_SESSION_ACTIVE){
                @session_start();
            }
            return !empty($name) ? ($_SESSION[$name] ?? $default) : $_SESSION;
        }
    }


    /**
     * 保存 session
     * @param string $name
     * @param $value
     */
    public static function set(string $name, $value){
        if(Context::isSwoole()){
            $php_session_id = Request::getCookie("PHP_SESSION_ID");
            if(empty($php_session_id)){
                $php_session_id = sha1(microtime(true).rand(0,999999));
                Request::setCookie("PHP_SESSION_ID", $php_session_id);
            }
            //
            $config = Config::get('http');
            $session_set = $config["session"];
            switch($session_set['driver']){
                case "middleware":
                    //使用中间键处理
                    self::processMiddleware('set', $name, $value);
                    break;
                default :
                    $file_path = $session_set['file_path'];
                    if(!is_dir($file_path)){
                        @mkdir($file_path, 0777, true);
                    }
                    $file_path = substr($file_path, -1) == '/' ? $file_path : "{$file_path}/";
                    //保存到文件
                    $file = $file_path.$php_session_id.".txt";
                    if(file_exists($file)){
                        $data = file_get_contents($file);
                        $data = !empty($data) ? @json_decode($data, true) : [];
                        $data[$name] = [
                            "value" => $value,
                            "time"  => time()
                        ];

                    }else{
                        $data = [
                            $name => [
                                "value"     => $value,
                                "time"      => time()
                            ]
                        ];
                    }
                    @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
                    break;
            }
        }else{
            if(session_status() !== PHP_SESSION_ACTIVE){
                @session_start();
            }
            $_SESSION[$name] = $value;
        }
    }

    /**
     * 移除 session
     * @param string|null $name
     */
    public static function drop(string $name = null){
        if(Context::isSwoole()) {
            $php_session_id = Request::getCookie("PHP_SESSION_ID");
            if(!empty($php_session_id)){
                $config = Config::get('http');
                $session_set = $config["session"];
                switch($session_set['driver']){
                    case "middleware":
                        //使用中间键处理
                        self::processMiddleware('drop', $name);
                        break;
                    default :
                        $file_path = $session_set['file_path'];
                        $file_path = substr($file_path, -1) == '/' ? $file_path : "{$file_path}/";
                        //保存到文件
                        $file = $file_path.$php_session_id.".txt";
                        if(file_exists($file)){
                            $data = file_get_contents($file);
                            $data = !empty($data) ? @json_decode($data, true) : [];
                            if(is_null($name)){
                                @unlink($file);
                            }elseif(isset($data[$name])){
                                unset($data[$name]);
                                if(!empty($data)){
                                    @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
                                }else{
                                    @unlink($file);
                                }
                            }
                        }
                        break;
                }
            }
        }else{
            if(session_status() !== PHP_SESSION_ACTIVE){
                @session_start();
            }
            if(is_null($name)){
                $_SESSION = [];
            }else{
                if(isset($_SESSION[$name])) unset($_SESSION[$name]);
            }
        }
    }

    private static function processMiddleware($method, $name, $value = null){
        $middlewares = Config::get('http.session.middleware');
        $middlewares = is_array($middlewares) ? $middlewares : [];
        $middleware = $middlewares[$method] ?? null;
        if(!is_null($middleware)){
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    call_user_func_array([$_class, $_method], [$name, $value]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if($m instanceof ISessionMiddleware){
                    call_user_func_array([$m, $method], [$name, $value]);
                }
            }elseif($middleware instanceof \Closure){
                call_user_func($middleware, [$name, $value]);
            }
        }
    }
}