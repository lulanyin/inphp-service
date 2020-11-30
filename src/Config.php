<?php
namespace Inphp\Service;

class Config
{
    public static $config = [];

    /**
     * 获取配置
     * @param string|null $path
     * @param null $default
     * @return array|mixed|string|null
     */
    public static function get(string $path = 'http', $default = null){
        if(empty(self::$config)){
            if(defined("INPHP_SERVICE_CONFIG")){
                if(is_array(INPHP_SERVICE_CONFIG)){
                    self::$config = INPHP_SERVICE_CONFIG;
                }elseif(is_file(INPHP_SERVICE_CONFIG)){
                    self::$config = require INPHP_SERVICE_CONFIG;
                }
            }
        }
        if(is_null($path)){
            return self::$config;
        }
        $keys = explode(".", $path);
        $here = self::$config;
        foreach ($keys as $key){
            if(isset($here[$key])){
                $here = is_object($here[$key]) ? (array)$here[$key] : $here[$key];
            }else{
                $here = null;
                break;
            }
        }
        return $here ?? $default;
    }
}