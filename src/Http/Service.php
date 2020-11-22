<?php
namespace Small\Service\Http;

use Small\Service\IService;
use Small\Service\Object\Client;
use Small\Service\Util\File;
use Swoole\Http\Server;
use Swoole\Process;

/**
 * 服务
 * Class Service
 * @package Small\Service\Http
 */
class Service implements IService
{
    /**
     * swoole http server
     * @var Server
     */
    private $http_server;

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
            $this->http_server = new Server($config['swoole']['http']['ip'], $config['swoole']['http']['port']);
            $this->http_server->set($config['swoole']['http']['settings']);
            $this->http_server->on("request", function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) use($config){
                //保存
                Container::setRequest($request);
                Container::setResponse($response);
                //主域名
                $host = $request->header['host'];
                $ip = $request->header['x-real-ip'] ?? ($request->server['remote_addr'] ?? null);
                //请求方式
                $method = $request->server['request_method'];
                //路径信息
                $path = $request->server['path_info'] ?? '';
                $uri = $request->server['request_uri'] ?? '';
                $path = !empty($path) ? $path : $uri;
                $http_x_requested_with = $request->header['http_x_requested_with'] ?? null;
                //交给路由处理，由HTTP控制器处理数据返回，得到数据
                Container::setClient(new Client([
                    "https"     => null,
                    "host"      => $host,
                    "ip"        => $ip,
                    "method"    => $method,
                    "ajax"      => $http_x_requested_with == 'XMLHttpRequest',
                    //"session"   => Session::get(),
                    "cookie"    => $request->cookie,
                    "get"       => $request->get,
                    "post"      => $request->post,
                    "files"     => $request->files,
                    "raw_post_data" => $request->rawContent(),
                    "uri"       => $path
                ]));
                // session 需要在之后设置
                Container::updateClient("session", Session::get());
                $status = (new Router())->start();
                //得到路由状态对象，交由 Response 处理，响应数据给客户端
                (new Response())->start($status)->send();
            });
            //自动重启线程
            $this->autoReloadProcessor = new Process([$this, "reload"]);
            $this->http_server->addProcess($this->autoReloadProcessor);
            $this->http_server->on("WorkerStart", function (Server $server, int $worker_id){
                if($worker_id == 0){
                    echo "主进程已启动...".PHP_EOL;
                    echo "运行自动重载线程...".PHP_EOL;
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
        print_r($_SERVER);
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
            $status = (new Router())->start();
            //得到路由状态对象，交由 Response 处理，响应数据给客户端
            (new Response())->start($status)->send();
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