<?php
namespace Inphp\Service\Http;

use Inphp\Service\IService;
use Inphp\Service\Object\Client;
use Inphp\Service\Util\File;
use Swoole\Http\Server;
use Swoole\Process;

/**
 * 服务
 * Class Service
 * @package Inphp\Service\Http
 */
class Service implements IService
{
    /**
     * swoole http server
     * @var Server
     */
    private $http_server;

    /**
     * swoole http server 绑定的IP
     * @var string
     */
    private $ip = '127.0.0.1';

    /**
     * Swoole http server 的端口
     * @var int
     */
    private $port = 1990;

    /**
     * 默认设置
     * @var array|mixed
     */
    private $server_setting = [
        //PID文件保存位置，文件夹必须存在
        'pid_file'              => ROOT.'/runtime/http_service.pid',
        //worker 数量，一般按CPU核心数量 * 2
        'worker_num'            => 2,
        //最大请求数量，按需，不可超过系统设置
        'max_request'           => 24,
        //最大连接数量
        'max_connection'        => 128,
        //
        'daemonize'             => 0,
        'dispatch_mode'         => 2,
        //日志文件，文件夹必须存在
        'log_file'              => ROOT.'/runtime/log/http_service.log',
        //默认异步进程数量
        'task_worker_num'       => 0,
        'package_max_length'    => 8092,
        'upload_tmp_dir'        => ROOT.'/runtime/upload',
        //默认静态文件目录，文件夹必须存在，一般使用nginx代理完成静态文件访问
        'document_root'         => ROOT.'/public',
        //文件上传保存文件夹
        'upload_dir'            => ROOT.'/public/attachment'
    ];

    //返回的控制器类型
    const CONTROLLER = 'controller';
    const HTML       = 'html';

    /**
     *
     * Service constructor.
     */
    public function __construct()
    {
        //常量默认
        !defined("SWOOLE") && define("SWOOLE", "swoole");
        !defined("FPM") && define("FPM", 'php-fpm');
        !defined("SERVICE_PROVIDER") && define("SERVICE_PROVIDER", FPM);
        $config = Container::getConfig();
        if(empty($config)){
            exit("未配置的服务");
        }
        if(SERVICE_PROVIDER == SWOOLE){
            //由 swoole 提供服务
            //处理获得请求路径，交给路由处理
            $this->ip = $config['swoole']['http']['ip'] ?? $this->ip;
            $this->port = $config['swoole']['http']['port'];
            $this->server_setting = $config['swoole']['http']['settings'] ?? $this->server_setting;
            $this->http_server = new Server($this->ip, $this->port);
            $this->http_server->set($this->server_setting);
            //
            $this->http_server->on("request", function (\Swoole\Http\Request $swoole_request, \Swoole\Http\Response $swoole_response) use($config){
                //保存
                Container::setRequest($swoole_request);
                //主域名
                $host = $swoole_request->header['host'];
                $ip = $swoole_request->header['x-real-ip'] ?? ($swoole_request->server['remote_addr'] ?? null);
                //请求方式
                $method = $swoole_request->server['request_method'];
                //路径信息
                $path = $swoole_request->server['path_info'] ?? '';
                $uri = $swoole_request->server['request_uri'] ?? '';
                $path = !empty($path) ? $path : $uri;
                $http_x_requested_with = $swoole_request->header['http_x_requested_with'] ?? null;
                //交给路由处理，由HTTP控制器处理数据返回，得到数据
                Container::setClient(new Client([
                    "https"     => null,
                    "host"      => $host,
                    "ip"        => $ip,
                    "method"    => $method,
                    "ajax"      => $http_x_requested_with == 'XMLHttpRequest',
                    //"session"   => Session::get(), 由于未初始化，不可使用 Session
                    "cookie"    => $swoole_request->cookie,
                    "get"       => $swoole_request->get,
                    "post"      => $swoole_request->post,
                    "files"     => $swoole_request->files,
                    "raw_post_data" => $swoole_request->rawContent(),
                    "uri"       => $path
                ]));
                //创建一个Response对象
                $response = new Response($swoole_response);
                Container::setResponse($response);
                // session 需要在之后设置
                Container::updateClient("session", Session::get());
                $status = (new Router())->start();
                //得到路由状态对象，交由 Response 处理，响应数据给客户端
                $response->start($status)->send();
            });
            //自动重启线程
            $this->autoReloadProcessor = new Process([$this, "reload"]);
            $this->http_server->addProcess($this->autoReloadProcessor);
            $this->http_server->on("WorkerStart", function (Server $server, int $worker_id){
                if($worker_id == 0){
                    $ip = $this->ip == '0.0.0.0' ? '127.0.0.1' : $this->ip;
                    echo "主进程已启动，web服务地址是：http://{$ip}:{$this->port}".PHP_EOL;
                    $this->autoReload($server);
                }
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
            //交给路由处理
            Container::setClient(new Client([
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
                "ajax"      => $http_x_requested_with == 'XMLHttpRequest'
            ]));
            //初始化一个 Response 对象
            $response = new Response();
            Container::setResponse($response);
            $status = (new Router())->start();
            //得到路由状态对象，交由 Response 处理，响应数据给客户端
            $response->start($status)->send();
        }
    }

    /**
     * 自动重启服务的线程
     * @var Process
     */
    public $autoReloadProcessor = null;

    /**
     * 自动重启，每5秒运行一次
     * @param Server $server
     */
    public function autoReload(Server $server){
        $config = Container::getConfig();
        if($this->autoReloadProcessor && $config['swoole']['http']['auto_reload']){
            $this->autoReloadProcessor->write("start");
            $server->after(5 * 1000, function () use ($server){
                $this->autoReload($server);
            });
        }
    }

    /**
     * 重启 swoole http server 服务
     * @param Process $process
     */
    public function reload(Process $process){
        while (true){
            $config = Container::getConfig();
            $version = RUNTIME."/version.json";
            $dirs = $config['swoole']['http']['listen_dir'];
            $files = [];
            $view_suffix = $config['router']['http']['view_suffix'] ?? 'html';
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
                    @file_put_contents($version, json_encode($list));
                    $this->http_server->reload();
                }
            }else{
                @file_put_contents($version, json_encode($list, JSON_UNESCAPED_UNICODE));
            }
            sleep(5);
        }
    }
}