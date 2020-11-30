<?php
namespace Inphp\Service\Http;

use Inphp\Service\Config;
use Inphp\Service\Context;
use Inphp\Service\Middleware\IServerOnRequestMiddleware;
use Inphp\Service\Object\Client;
use Inphp\Service\Router;
use Inphp\Service\Service;
use Swoole\Coroutine;
use Swoole\Process;

/**
 * 服务端
 * Class Server
 * @package Inphp\Service\Http
 */
class Server extends \Inphp\Service\Server
{
    /**
     * 监听端口
     * @var int
     */
    public $port = 1990;

    /**
     * 服务类型
     * @var string
     */
    public $server_type = Service::HTTP;

    /**
     * @var \Swoole\Http\Server
     */
    public $server = null;

    /**
     * 初始化
     * Server constructor.
     * @param false $swoole
     */
    public function __construct($swoole = false)
    {
        //常量默认
        !defined("SWOOLE") && define("SWOOLE", "swoole");
        !defined("FPM") && define("FPM", 'php-fpm');
        //默认常规的PHP-FPM
        !defined("INPHP_SERVICE_PROVIDER") && define("INPHP_SERVICE_PROVIDER", $swoole ? SWOOLE : FPM);

        $config = Config::get('http');
        $this->ip = $config['ip'];
        $this->port = $config['port'];
        $this->settings = array_merge($this->settings, $config['settings']);

        if(INPHP_SERVICE_PROVIDER == SWOOLE){
            //协程
            Coroutine::set(['hook_flags' => SWOOLE_HOOK_TCP]);
            //使用 swoole\http\server
            $this->server = new \Swoole\Http\Server($this->ip, $this->port, SWOOLE_PROCESS, $config['sock_type'] ?? SWOOLE_SOCK_TCP);
            //各事件...
            $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
            $this->server->on('start', [$this, 'onStart']);
            $this->server->on('request', [$this, 'onRequest']);
            $this->server->on('task', [$this, 'onTask']);
            $this->server->on('finish', [$this, 'onFinish']);
            //如果启用了热更新，则运行一条进程
            if($config['hot_update']['enable']){
                $this->hot_update_processor = new Process([$this, 'hotUpdate']);
                $this->server->addProcess($this->hot_update_processor);
            }
        }
    }

    /**
     * 接收到请求
     * @param \Swoole\Http\Request|null $request
     * @param \Swoole\Http\Response|null $response
     */
    public function onRequest(\Swoole\Http\Request $request = null, \Swoole\Http\Response $response = null){
        if($this->server){
            //swoole
            //主域名
            $host = $request->header['host'];
            $ip = $swoole_request->header['x-real-ip'] ?? ($request->server['remote_addr'] ?? null);
            //请求方式
            $method = $request->server['request_method'];
            //路径信息
            $path = $request->server['path_info'] ?? '';
            $uri = $request->server['request_uri'] ?? '';
            $path = !empty($path) ? $path : $uri;
            $http_x_requested_with = $request->header['x-requested-with'] ?? null;
            //交给路由处理，由HTTP控制器处理数据返回，得到数据
            $client = new Client([
                "https"     => ($request->header['X-Request-Scheme'] ?? null) == 'https',
                "host"      => $host,
                "ip"        => $ip,
                "method"    => $method,
                "ajax"      => $http_x_requested_with == 'XMLHttpRequest',
                //"session"   => Session::get(), 由于未初始化，不可使用 Session
                "cookie"    => $request->cookie,
                "get"       => $request->get,
                "post"      => $request->post,
                "files"     => $request->files,
                "raw_post_data" => $request->rawContent(),
                "uri"       => $path,
                "origin"    => $request->header['origin'] ?? '',
                "id"        => $request->fd
            ]);
            //将swoole\http\request 保存到上下文
            Context::set('request', $request);
        }else{
            //php-fpm/fast-cgi
            //session 开启
            if(session_status() !== PHP_SESSION_ACTIVE){
                @session_start();
            }
            $host = $_SERVER['HTTP_HOST'];
            $ip = $_SERVER['REMOTE_ADDR'];
            $method = $_SERVER['REQUEST_METHOD'];
            $path = $_SERVER['PATH_INFO'] ?? "";
            $uri = $_SERVER['REQUEST_URI'] ?? "";
            $path = !empty($path) ? $path : $uri;
            $http_x_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
            //保存客户端数据
            $client = new Client([
                "https"     => $_SERVER['REQUEST_SCHEME'] == "https",
                "host"      => $host,
                "ip"        => $ip,
                "method"    => $method,
                "session"   => $_SESSION,
                "cookie"    => $_COOKIE,
                "get"       => $_GET,
                "post"      => $_POST,
                "files"     => $_FILES,
                "raw_post_data" => $GLOBALS['HTTP_RAW_POST_DATA'] ?? null,
                "uri"       => $path,
                "ajax"      => $http_x_requested_with == 'XMLHttpRequest',
                "id"        => 0,
                "origin"    => ""
            ]);
        }
        //保存客户端数据到上下文
        Context::setClient($client);
        //更新
        Context::getClient()->set('session', Session::get());
        //使用统一对象
        $_response = new Response($response);
        //保存到上下文
        Context::set('response', $_response);
        //中间键
        $middlewares = Config::get('http.middleware.on_request', []);
        $middlewares = is_array($middlewares) ? $middlewares : [];
        foreach ($middlewares as $middleware){
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    call_user_func_array([$_class, $_method], [$this, $request, $_response]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if($m instanceof IServerOnRequestMiddleware){
                    $m->process($this, $request, $_response);
                }
            }elseif($middleware instanceof \Closure){
                call_user_func($middleware, [$this, $request, $_response]);
            }
        }
        //路由处理
        $status = Router::process($client->uri, $client->method, $this->server_type);
        //得到状态数据，响应数据
        $_response->start($status)->send();
    }

    /**
     * 服务启动
     */
    public function start(){
        //启动前
        $this->beforeStart();
        //区分处理
        if($this->server){
            //启动 swoole http 服务
            $this->server->start();
        }else{
            //php-fpm / fast-cgi
            $this->onRequest();
        }
    }

    /**
     * 重启服务
     */
    public function reload(){
        if($this->server){
            $this->server->reload();
        }
    }
}