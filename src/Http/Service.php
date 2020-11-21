<?php
namespace Small\Service\Http;

use Small\Service\IService;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

class Service implements IService
{

    private $config = [];

    private $http_server;

    public function __construct()
    {
        //常量默认
        !defined("SWOOLE") && define("SWOOLE", "swoole");
        !defined("FPM") && define("FPM", 'php-fpm');
        !defined("SERVICE_PROVIDER") && define("SERVICE_PROVIDER", FPM);
        $config = $this->getConfig();
        if(empty($config)){
            exit("未配置的服务");
        }
        if(SERVICE_PROVIDER == SWOOLE){
            //由 swoole 提供服务
            //处理获得请求路径，交给路由处理
            $this->http_server = new Server($config['swoole']['http']['ip'], $config['swoole']['http']['port']);
            $this->http_server->set($config['swoole']['http']['settings']);
            $this->http_server->on("request", function (Request $request, Response $response){
                //请求方式
                $method = $request->server['request_method'];
                //路径信息
                $path = $request->server['path_info'] ?? '';
                $uri = $request->server['request_uri'] ?? '';

                //交给路由处理，由HTTP控制器处理数据返回，得到数据


            });
        }else{
            //常规 php-fpm

        }
    }

    /**
     * 服务启动
     */
    public function start()
    {
        // TODO: Implement start() method.
        if(SERVICE_PROVIDER == SWOOLE){
            $this->http_server->start();
        }else{

        }
    }

    /**
     * 获取配置
     * @return array|null
     */
    public function getConfig(){
        if(!empty($this->config)){
            return $this->config;
        }
        if(defined("SERVICE_CONFIG")){
            if(is_array(SERVICE_CONFIG)){
                $this->config = SERVICE_CONFIG;
            }elseif(is_file(SERVICE_CONFIG)){
                $this->config = require SERVICE_CONFIG;
            }
            return $this->config;
        }
        return null;
    }
}