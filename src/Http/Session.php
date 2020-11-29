<?php
namespace Inphp\Service\Http;

use Inphp\Service\ISessionMiddleWare;

class Session
{

    /**
     * 获取 session
     * @param string|null $name
     * @param null $default
     * @return array|mixed|null
     */
    public static function get(string $name = null, $default = null){
        if(Container::isSwoole()) {
            $php_session_id = Request::getCookie("PHP_SESSION_ID");
            if(!empty($php_session_id)){
                //获取
                $config = Container::getConfig();
                $session_set = $config['swoole']["http"]["session"];
                switch($session_set['driver']){
                    case "middleware":
                        //使用中间键处理
                        $smName = $session_set['middleware']["get"];
                        if(!empty($smName) && class_exists($smName)){
                            $sm = new $smName($php_session_id);
                            if($sm instanceof ISessionMiddleWare){
                                return $sm->get($name, $default);
                            }
                        }
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
        if(Container::isSwoole()){
            $php_session_id = Request::getCookie("PHP_SESSION_ID");
            if(empty($php_session_id)){
                $php_session_id = sha1(microtime(true).rand(0,999999));
                Request::setCookie("PHP_SESSION_ID", $php_session_id);
            }
            //
            $config = Container::getConfig();
            $session_set = $config['swoole']["http"]["session"];
            switch($session_set['driver']){
                case "middleware":
                    //使用中间键处理
                    $smName = $session_set['middleware']["set"];
                    if(!empty($smName) && class_exists($smName)){
                        $sm = new $smName($php_session_id);
                        if($sm instanceof ISessionMiddleWare){
                            $sm->set($name, $value);
                        }
                    }
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
        if(Container::isSwoole()) {
            $php_session_id = Request::getCookie("PHP_SESSION_ID");
            if(!empty($php_session_id)){
                $config = Container::getConfig();
                $session_set = $config['swoole']["http"]["session"];
                switch($session_set['driver']){
                    case "middleware":
                        //使用中间键处理
                        $smName = $session_set['middleware']["drop"];
                        if(!empty($smName) && class_exists($smName)){
                            $sm = new $smName($php_session_id);
                            if($sm instanceof ISessionMiddleWare){
                                $sm->drop($name);
                            }
                        }
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
}