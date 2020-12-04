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

use Inphp\Service\Middleware\IServerBeforeStartMiddleware;
use Inphp\Service\Middleware\IServerOnFinishMiddleware;
use Inphp\Service\Middleware\IServerOnStartMiddleware;
use Inphp\Service\Middleware\IServerOnTaskMiddleware;
use Inphp\Service\Middleware\IServerOnWorkerStartMiddleware;
use Swoole\Process;

abstract class Server
{
    /**
     * 绑定IP
     * @var string
     */
    public $ip = '0.0.0.0';

    /**
     * 端口
     * @var int
     */
    public $port = 1990;

    /**
     * @var \Swoole\Http\Server|\Swoole\WebSocket\Server
     */
    public $server = null;

    /**
     * 服务类型
     * @var string
     */
    public $server_type = Service::HTTP;

    /**
     * 配置
     * @var array
     */
    public $settings = [
        //开启异步风格服务器的协程支持
        'enable_coroutine' => true,
        //不可超过CPU核心数 * 4，必须小于等于 worker_num
        'reactor_num'   => 4,
        //CPU的1~4倍为合理
        'worker_num'    => 4,
        //Task进程数量
        'task_worker_num' => 4,
        //Task开启协程
        'task_enable_coroutine' => true,
        //worker 进程的最大任务数，超过此任务数据，将会退出并释放所有资源
        'max_request'   => 100,
        //最小值为 (worker_num + task_worker_num) * 2 + 32
        'max_connection'=> 1000,
        //数据包分发策略
        'dispatch_mode' => 2,
        //守护进程
        'daemonize' => 0
    ];

    /**
     * 热更新进程
     * @var Process
     */
    public $hot_update_processor;


    /**
     * 服务启动前
     *
     */
    public function beforeStart(){
        //中间键
        $middleware_list = Config::get($this->server_type.'.middleware.before_start', []);
        $middleware_list = is_array($middleware_list) ? $middleware_list : [];
        foreach ($middleware_list as $middleware){
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    call_user_func_array([$_class, $_method], [$this]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if($m instanceof IServerBeforeStartMiddleware){
                    $m->process($this);
                }
            }elseif($middleware instanceof \Closure){
                call_user_func($middleware, [$this]);
            }
        }
    }

    /**
     * @param \Swoole\Http\Server $server
     */
    public function onStart(\Swoole\Http\Server $server){
        //服务启动，清除遗留缓存
        if($this->server_type == Service::WS){
            Cache::clean();
        }
        $ip = $this->ip == '0.0.0.0' ? '127.0.0.1' : $this->ip;
        echo "[".($this->server_type == Service::WS ? "websocket" : $this->server_type)."]服务已启动，地址是：{$this->server_type}://{$ip}:{$this->port}".PHP_EOL;
        $config = Config::get($this->server_type);
        if($config['hot_update']['enable'] && $this->hot_update_processor){
            $this->hot_update_processor->write('start');
        }
        //中间键
        $middleware_list = Config::get($this->server_type.'.middleware.on_start', []);
        $middleware_list = is_array($middleware_list) ? $middleware_list : [];
        foreach ($middleware_list as $middleware){
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    call_user_func_array([$_class, $_method], [$server]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if($m instanceof IServerOnStartMiddleware){
                    $m->process($server);
                }
            }elseif($middleware instanceof \Closure){
                call_user_func($middleware, [$server]);
            }
        }
    }

    /**
     * 每个进程启动
     * @param \Swoole\Http\Server $server
     * @param int $worker_id
     */
    public function onWorkerStart(\Swoole\Http\Server $server, int $worker_id = 0){
        //中间键
        $middleware_list = Config::get($this->server_type.'.middleware.on_worker_start', []);
        $middleware_list = is_array($middleware_list) ? $middleware_list : [];
        foreach ($middleware_list as $middleware){
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    call_user_func_array([$_class, $_method], [$server, $worker_id]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if($m instanceof IServerOnWorkerStartMiddleware){
                    $m->process($server, $worker_id);
                }
            }elseif($middleware instanceof \Closure){
                call_user_func($middleware, [$server, $worker_id]);
            }
        }
    }

    /**
     * 异步任务投递
     * @param \Swoole\Http\Server $server
     * @param int $task_id
     * @param int $worker_id
     * @param $data
     */
    public function onTask(\Swoole\Http\Server $server, int $task_id, int $worker_id, $data){
        //中间键
        $middleware_list = Config::get($this->server_type.'.middleware.on_task', []);
        $middleware_list = is_array($middleware_list) ? $middleware_list : [];
        foreach ($middleware_list as $middleware){
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    call_user_func_array([$_class, $_method], [$server, $task_id, $worker_id, $data]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if($m instanceof IServerOnTaskMiddleware){
                    $m->process($server, $task_id, $worker_id, $data);
                }
            }elseif($middleware instanceof \Closure){
                call_user_func($middleware, [$server, $task_id, $worker_id, $data]);
            }
        }
    }

    /**
     * 异步任务完成
     * @param $server
     * @param $task_id
     * @param $data
     */
    public function onFinish(\Swoole\Http\Server $server, int $task_id, $data){
        //中间键
        $middleware_list = Config::get($this->server_type.'.middleware.on_finish', []);
        $middleware_list = is_array($middleware_list) ? $middleware_list : [];
        foreach ($middleware_list as $middleware){
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    call_user_func_array([$_class, $_method], [$server, $task_id, $data]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if($m instanceof IServerOnFinishMiddleware){
                    $m->process($server, $task_id, $data);
                }
            }elseif($middleware instanceof \Closure){
                call_user_func($middleware, [$server, $task_id, $data]);
            }
        }
    }

    /**
     * 热更新
     * @param Process $process
     */
    public function hotUpdate(Process $process){
        while (true){
            Service::hotUpdate($this->server_type, function (){
                $this->reload();
            });
        }
    }

}