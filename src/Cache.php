<?php
/**
 * Create By Hunter
 * 2020/12/1 10:35 上午
 *
 */
namespace Inphp\Service;

use Inphp\Service\Middleware\ICacheMiddleware;
use Inphp\Service\Util\File;

class Cache
{
    /**
     * 获取缓存数据
     * @param $name
     * @param $default
     * @param null $cache_path
     * @return false|mixed|string|null
     */
    public static function get($name, $default = null, $cache_path = null){
        $config = Config::get('cache');
        if($config['driver'] == 'middleware'){
            return self::processMiddleware("get", $name, $default);
        }else{
            $cache_path = $cache_path ?? $config['path'];
            if(is_dir($cache_path)){
                $uri = $cache_path."/".$name;
                if(is_file($uri)){
                    $data = file_get_contents($uri);
                    $value = json_decode($data, true);
                    return null === $value ? $data : $value;
                }
            }
        }
        return $default;
    }

    /**
     * @param $name
     * @param $value
     * @param null $cache_path
     */
    public static function set($name, $value, $cache_path = null){
        $config = Config::get('cache');
        if($config['driver'] == 'middleware'){
            self::processMiddleware("set", $name, $value);
        }else{
            $cache_path = $cache_path ?? $config['path'];
            if(!is_dir($cache_path)){
                @mkdir($cache_path, 0777, true);
            }
            if(is_dir($cache_path)){
                $uri = $cache_path."/".$name;
                file_put_contents($uri, is_array($value) || is_object($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value);
            }
        }
    }

    /**
     * 移除缓存
     * @param $name
     * @param null $cache_path
     */
    public static function remove($name, $cache_path = null){
        $config = Config::get('cache');
        if($config['driver'] == 'middleware'){
            self::processMiddleware("remove", $name);
        }else{
            $cache_path = $cache_path ?? $config['path'];
            if(is_dir($cache_path)){
                $uri = $cache_path."/".$name;
                if(is_file($uri)){
                    @unlink($uri);
                }
            }
        }
    }

    /**
     * 清除所有缓存
     */
    public static function clean(){
        $config = Config::get('cache');
        if($config['driver'] == 'middleware'){
            self::processMiddleware("clean");
        }else{
            $cache_path = $cache_path ?? $config['path'];
            File::clearDir($cache_path);
        }
    }

    /**
     * 统一处理中间键
     * @param $method
     * @param $name
     * @param null $value
     * @return mixed|null
     */
    private static function processMiddleware($method, $name = null, $value = null){
        $middleware = Config::get('cache.middleware');
        $middleware = is_array($middleware) ? $middleware : [];
        $middleware = $middleware[$method] ?? null;
        if(!is_null($middleware)){
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    return call_user_func_array([$_class, $_method], [$name, $value]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if($m instanceof ICacheMiddleware){
                    return call_user_func_array([$m, $method], [$name, $value]);
                }
            }elseif($middleware instanceof \Closure){
                return call_user_func($middleware, [$name, $value]);
            }
        }
        return $value;
    }
}