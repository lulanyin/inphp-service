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
namespace Inphp\Service;

use Inphp\Service\Util\File;

/**
 * 服务入口
 * Class Service
 * @package Inphp\Service
 */
class Service implements IService
{
    /**
     * @var Websocket\Server|Http\Server
     */
    public $server;

    const HTTP = 'http';
    const WS = 'ws';

    public function __construct($swoole = false, $ws = false)
    {
        $ws = $swoole && $ws;
        $this->server = $ws ? new Websocket\Server()  : new Http\Server($swoole);
        if($swoole){
            //运行 server 服务，由 Swoole 拓展支持
            echo "+---------------------+\r\n";
            echo "| ♪♪♪♪♪♪ INPHP ♪♪♪♪♪♪ |\r\n";
            echo "| Think you for using |\r\n";
            echo "|  support by swoole  |\r\n";
            echo "+---------------------+\r\n";
        }
    }

    public function start()
    {
        // TODO: Implement start() method.
        $this->server->start();
    }

    /**
     * 热更新
     * @param $type
     * @param $callback
     */
    public static function hotUpdate($type, $callback){
        $config = Config::get($type == self::WS ? self::WS : self::HTTP);
        $hot_update = $config['hot_update'];
        $version = $hot_update['version_file'];
        $dirs = $hot_update['listen_dir'] ?? [];
        if(empty($dirs)){
            return;
        }
        $files = [];
        $view_suffix = $config['view_suffix'] ?? 'html';
        foreach ($dirs as $dir){
            $files = array_merge($files, File::getAllFiles($dir, "php|{$view_suffix}|json"));
        }
        $list = [];
        foreach ($files as $item){
            $list[] = [
                "md5" => $item['md5'],
                "file"=> $item['path']
            ];
        }
        if(is_file($version)){
            $md5_old = md5_file($version);
            $md5_new = md5(json_encode($list, JSON_UNESCAPED_UNICODE));
            if($md5_old != $md5_new){
                echo date("Y/m/d H:i:s")." server restart ...\r\n";
                //apc_clear_cache();
                @opcache_reset();
                @file_put_contents($version, json_encode($list, JSON_UNESCAPED_UNICODE));
                if($callback instanceof \Closure){
                    $callback();
                }
            }
        }else{
            @file_put_contents($version, json_encode($list, JSON_UNESCAPED_UNICODE));
        }
        //间隔
        sleep($hot_update['seconds'] ?? 10);
    }
}