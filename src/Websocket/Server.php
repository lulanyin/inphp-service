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
namespace Inphp\Service\Websocket;

use Inphp\Service\Config;
use Inphp\Service\Context;
use Inphp\Service\Middleware\IServerOnCloseMiddleware;
use Inphp\Service\Middleware\IServerOnMessageMiddleware;
use Inphp\Service\Middleware\IServerOnOpenMiddleware;
use Inphp\Service\Object\Client;
use Inphp\Service\Object\Message;
use Inphp\Service\Router;
use Inphp\Service\Service;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Process;
use Swoole\WebSocket\Frame;

class Server extends \Inphp\Service\Server
{
    /**
     * 监听端口
     * @var int
     */
    public $port = 1990;

    /**
     * @var \Swoole\WebSocket\Server;
     */
    public $server = null;

    /**
     * 服务类型
     * @var string
     */
    public $server_type = Service::WS;

    /**
     * 热启动进程
     * @var Process
     */
    public $hot_update_processor = null;

    /**
     * 初始化
     * Server constructor.
     */
    public function __construct()
    {
        //常量默认
        !defined("SWOOLE") && define("SWOOLE", "swoole");
        !defined("FPM") && define("FPM", 'php-fpm');
        //默认常规的PHP-FPM
        !defined("INPHP_SERVICE_PROVIDER") && define("INPHP_SERVICE_PROVIDER", SWOOLE);

        $config = Config::get(Service::WS);
        $this->ip = $config['ip'];
        $this->port = $config['port'];
        $this->settings = array_merge($this->settings, $config['settings']);
        //协程
        Coroutine::set(['hook_flags' => SWOOLE_HOOK_TCP]);
        $this->server = new \Swoole\WebSocket\Server($this->ip, $this->port, SWOOLE_PROCESS, $config['sock_type'] ?? SWOOLE_SOCK_TCP);
        //各事件...
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('start', [$this, 'onStart']);
        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('task', [$this, 'onTask']);
        $this->server->on('finish', [$this, 'onFinish']);
        //如果启用了热更新，则运行一条进程
        if($config['hot_update']['enable']){
            $this->hot_update_processor = new Process([$this, 'hotUpdate']);
            $this->server->addProcess($this->hot_update_processor);
        }

    }

    /**
     * 客户端握手成功后，会触发这个，如果私自设置了握手回调，则不会触发这个事件
     * @param \Swoole\WebSocket\Server $server
     * @param Request $request
     */
    public function onOpen(\Swoole\WebSocket\Server $server, Request $request){
        //保存客户端ID到当前协程
        Context::setClientId($request->fd);
        //主域名
        $host = $request->header['host'];
        $ip = $swoole_request->header['x-real-ip'] ?? ($request->server['remote_addr'] ?? null);
        //路径信息
        $path = $request->server['path_info'] ?? '';
        $uri = $request->server['request_uri'] ?? '';
        $path = !empty($path) ? $path : $uri;
        //交给路由处理，由HTTP控制器处理数据返回，得到数据
        $client = new Client([
            "host"      => $host,
            "ip"        => $ip,
            "method"    => 'upgrade',
            "cookie"    => $request->cookie,
            "get"       => $request->get,
            "post"      => $request->post,
            "files"     => $request->files,
            "raw_post_data" => $request->rawContent(),
            "uri"       => $path,
            "origin"    => $request->header['origin'] ?? '',
            "id"        => $request->fd
        ]);
        //
        Context::setClient($client, $request->fd);
        //中间键
        $middleware_list = Config::get('http.middleware.on_open', []);
        $middleware_list = is_array($middleware_list) ? $middleware_list : [];
        foreach ($middleware_list as $middleware){
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    call_user_func_array([$_class, $_method], [$server, $request]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if($m instanceof IServerOnOpenMiddleware){
                    $m->process($server, $request);
                }
            }elseif($middleware instanceof \Closure){
                call_user_func($middleware, [$server, $request]);
            }
        }
    }

    /**
     * 客户端连接已关闭
     * @param \Swoole\WebSocket\Server $server
     * @param int $fd
     * @param int $reactor_id
     */
    public function onClose(\Swoole\WebSocket\Server $server, int $fd, int $reactor_id){
        Context::setClientId($fd);
        //中间键
        $middleware_list = Config::get($this->server_type.'.middleware.on_close', []);
        $middleware_list = is_array($middleware_list) ? $middleware_list : [];
        foreach ($middleware_list as $middleware){
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    call_user_func_array([$_class, $_method], [$server, $fd, $reactor_id]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if($m instanceof IServerOnCloseMiddleware){
                    $m->process($server, $fd, $reactor_id);
                }
            }elseif($middleware instanceof \Closure){
                call_user_func($middleware, [$server, $fd, $reactor_id]);
            }
        }
        Context::removeClient($fd);
        Context::clean($fd);
    }

    /**
     * 接收到客户端消息
     * @param \Swoole\WebSocket\Server $server
     * @param Frame $frame
     */
    public function onMessage(\Swoole\WebSocket\Server $server, Frame $frame){
        //保存client_id到当前协程上下文
        Context::setClientId($frame->fd);
        //将server对象保存到当前协程上下文
        Context::setServer($server);
        //frame 也保存进去
        Context::setFrame($frame);
        //中间键
        $middleware_list = Config::get($this->server_type.'.middleware.on_message', []);
        $middleware_list = is_array($middleware_list) ? $middleware_list : [];
        foreach ($middleware_list as $middleware){
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    call_user_func_array([$_class, $_method], [$server, $frame]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if($m instanceof IServerOnMessageMiddleware){
                    $m->process($server, $frame);
                }
            }elseif($middleware instanceof \Closure){
                call_user_func($middleware, [$server, $frame]);
            }
        }
        //仅允许接收JSON数据格式
        $json = !empty($frame->data) ? @json_decode($frame->data, true) : [];
        $json = is_array($json) ? $json : ["data" => $frame->data];
        $uri = $json['event'] ?? ($json['uri'] ?? '/');
        $status = Router::process($uri, 'upgrade', $this->server_type);
        //保存路由状态到协程上下文
        Context::setStatus($status);
        $message = new Message($json);
        //将消息保存到协程上下文
        Context::setMessage($message);
        //得到状太，执行控制器
        if($status->status == 200){
            if(!empty($status->controller) && class_exists($status->controller)){
                //仅处理能找到控制器的，其它数据一致不处理
                $controller = new $status->controller();
                //中间键
                $this->processMiddleware($server, $frame, 'before_execute');
                if(method_exists($controller, $status->method)){
                    call_user_func_array([$controller, $status->method], [$server, $frame, $message]);
                    return;
                }
            }
        }
        //未知数据
        $this->processMiddleware($server, $frame, 'unknow_data');
    }

    /**
     * 未知数据处理
     * @param \Swoole\WebSocket\Server $server
     * @param Frame $frame
     * @param string $part
     */
    private function processMiddleware(\Swoole\WebSocket\Server $server, Frame $frame, string $part){
        //中间键
        $middleware_list = Config::get($this->server_type.'.middleware.'.$part, []);
        $middleware_list = is_array($middleware_list) ? $middleware_list : [];
        foreach ($middleware_list as $middleware){
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    call_user_func_array([$_class, $_method], [$server, $frame]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if(method_exists($m, 'process')){
                    $m->process($server, $frame);
                }
            }elseif($middleware instanceof \Closure){
                call_user_func($middleware, [$server, $frame]);
            }
        }
    }

    /**
     * 服务启动
     */
    public function start(){
        //启动前
        $this->beforeStart();
        //启动服务
        $this->server->start();
    }

    /**
     * 重启服务
     */
    public function reload(){
        $this->server->reload();
    }
}