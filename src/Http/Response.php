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
namespace Inphp\Service\Http;

use Inphp\Service\Config;
use Inphp\Service\Context;
use Inphp\Service\IResponse;
use Inphp\Service\Middleware\IServerOnResponseMiddleware;
use Inphp\Service\Object\Message;
use Inphp\Service\Object\Status;

/**
 * 响应类
 * Class Response
 * @package Inphp\Service\Http
 */
class Response implements IResponse
{
    /**
     * @var \Swoole\Http\Response
     */
    public $swoole_response = null;

    /**
     * cookies
     * @var array
     */
    public $cookies = [];

    /**
     * 编码
     * @var string
     */
    public $charset = "UTF-8";

    /**
     * header
     * @var array
     */
    public $headers = [];

    /**
     * 主体内容
     * @var null
     */
    public $content = null;

    /**
     * http状态码
     * @var int
     */
    public $status_code = 200;

    /**
     * 状态数据
     * @var Status
     */
    public $status;

    /**
     * http版本，1.1, 2.0
     * @var string
     */
    public $version = "1.1";

    /**
     * 控制器的路径
     * @var string
     */
    public $path = '';

    /**
     * 是否已发送过响应
     * @var bool 
     */
    public $hasSend = false;

    /**
     * 控制器执行后，返回的数据
     * @var null
     */
    public $controller_result = null;

    /**
     * 响应内容类型，仅支持 json 或 默认
     * @var string
     */
    public $content_type = 'default';

    /**
     * 初始化
     * Response constructor.
     * @param \Swoole\Http\Response|null $response
     */
    public function __construct(\Swoole\Http\Response $response = null)
    {
        $this->swoole_response = $response;
    }

    /**
     * 处理响应状态
     * @param Status $status
     * @return Response
     */
    public function start(Status $status)
    {
        //状态码
        $this->withStatus($status->status);
        $this->path = $status->path;
        $this->status = $status;
        //谷歌浏览器、OPTIONS、PUT、DELETE请求
        if(in_array($status->state, ['favicon.ico', 'OPTIONS', 'PUT', 'DELETE'])){
            //跨域options请求，应该需要处理
            $client = Context::getClient();
            $this->agreeHost($client->host)->send();
            return $this;
        }
        //站点配置
        $config = Config::get('http');
        //内容类型
        $response_type = $status->response_content_type ?? 'default';
        $this->content_type = $response_type;
        $controller = null;
        if($status->status == 200){
            if(!empty($status->controller)){
                //控制器
                $controllerName = $status->controller;
                $controller = new $controllerName();
            }
        }
        //控制器执行前处理中间键
        $this->processMiddleware($controller, $status->method, 'before_execute');
        if($this->hasSend){
            return $this;
        }
        //执行
        if(!is_null($controller)){
            if(method_exists($controller, $status->method)){
                $result = $controller->{$status->method}();
                if($result instanceof Response){
                    //$result->send();
                }elseif ($result instanceof Message){
                    $this->withJson($result->toJson())->send();
                    return $this;
                }elseif (is_string($result) || is_object($result) || is_array($result)){
                    //控制器有数据返回
                    if(stripos($response_type, 'json') !== false){
                        $this->withJson([
                            "error"     => 0,
                            "message"   => 'success',
                            "data"      => $result
                        ]);
                    }else{
                        //将数据保存在全局中
                        $this->controller_result = $result;
                    }
                }elseif(!empty($this->content)){
                    //已经设置有内容
                    //$this->send();
                }else{
                    //其它...
                }
            }
        }

        //发送前处理中间键
        $this->processMiddleware($controller, $status->method, 'before_send');
        if($this->hasSend){
            return $this;
        }
        //可以使用中间键完成模板渲染，如果都未处理，则默认使用PHP去处理视图文件
        if(empty($this->content) && $status->status == 200){
            if(stripos($response_type, 'json') !== false){
                //使用json数据响应
                $this->withJson([
                    "error"     => 0,
                    "status"    => 200,
                    "message"   => 'success',
                    "data"      => $this->controller_result
                ]);
            }else{
                //常规内容响应
                $view_dir = $status->view_dir;
                $view_dir = strrchr($view_dir, "/") == "/" ? $view_dir : "{$view_dir}/";
                $file = $view_dir.$this->path."/".$status->view;
                $file = str_replace("//", "/", $file);
                if(file_exists($file)){
                    //还未设置响应内容，默认展示模板
                    $view_php = $config['view_php'] ?? false;
                    if($view_php){
                        //允许执行PHP
                        (function() use($file){
                            //隔离
                            ob_start();
                            include $file;
                            $this->withHTML(ob_get_contents());
                            ob_clean();
                        })();
                    }else{
                        //不执行PHP，按文件内容展示
                        $this->withHTML(file_get_contents($file));
                    }
                }else{
                    $this->withContent($this->controller_result);
                }
            }
        }
        
        if($status->status != 200){
            if($response_type == 'json'){
                $this->withJson([
                    'error'     => $status->status,
                    'status'    => $status->status,
                    'message'   => $status->message
                ]);
            }else{
                $this->withContent($status->message);
            }
        }

        //返回本身
        return $this;
    }

    /**
     * 执行中间键
     * @param $controller
     * @param $method
     * @param $step
     */
    private function processMiddleware($controller, $method, $step){
        //配置
        $config = Config::get('http');
        //中间键
        $middleware_list = $config['middleware'] ?? [];
        //控制器执行前处理中间键
        $middleware_list = $middleware_list[$step] ?? [];
        $middleware_list = is_string($middleware_list) ? [$middleware_list] : (is_array($middleware_list) ? $middleware_list : []);
        if(!empty($middleware_list)){
            foreach ($middleware_list as $middleware){
                if(is_array($middleware)){
                    //[__class__, 'static method']
                    $_class = $middleware[0];
                    $_method = $middleware[1] ?? null;
                    if(class_exists($_class) && !empty($_method)){
                        call_user_func_array([$_class, $_method], [$this, $controller, $method]);
                    }
                }elseif(is_string($middleware) && class_exists($middleware)){
                    $m = new $middleware();
                    if($m instanceof IServerOnResponseMiddleware){
                        $m->process($this, $controller, $method);
                    }
                }elseif($middleware instanceof \Closure){
                    call_user_func($middleware, [$this, $controller, $method]);
                }
            }
        }
    }
    
    /**
     * 设置header
     * @param $key
     * @param $value
     * @return Response
     */
    public function withHeader(string $key, $value){
        $this->headers[$key] = is_array($value) ? $value : [$value];
        return $this;
    }

    /**
     * 输出header
     * @param string $name
     * @param string $value
     */
    public function header(string $name, string $value)
    {
        // TODO: Implement header() method.
        if($this->swoole_response){
            $this->swoole_response->header($name, $value);
        }else{
            header($name.":".$value, false, $this->status_code);
        }
    }

    /**
     * 增加header
     * @param $key
     * @param $value
     * @return Response
     */
    public function withAddHeader(string $key, $value){
        if(!is_array($value)){
            $value = [$value];
        }
        if(isset($this->headers[$key])){
            $this->headers[$key] = array_merge($this->headers[$key], $value);
        }else{
            $this->headers[$key] = $value;
        }
        return $this;
    }

    /**
     * 设置cookie
     * @param string $name
     * @param string|null $value
     * @param int $time
     * @return Response
     */
    public function withCookie(string $name, string $value = null, $time = 3600){
        $this->cookies[] = [
            "name"      => $name,
            "value"     => $value,
            "time"      => $time
        ];
        return $this;
    }

    /**
     * 保存cookie
     * @param string $name
     * @param string|null $value
     * @param int $time
     * @return Response
     */
    public function cookie(string $name, string $value = null, int $time = 3600){
        return $this->withCookie($name, $value, $time);
    }

    /**
     * 输出cookie
     */
    public function sendCookies(){
        $config = Config::get('http');
        //获取客户端
        $client = Context::getClient();
        //跨域共享域名
        $domains = $config['cookie']['domains'] ?? [];
        $domains = is_array($domains) ? $domains : [];
        //得到当前请求的域名
        $domains[] = $client->host;
        foreach ($this->cookies as $cookie){
            $name = $cookie['name'];
            $value = $cookie['value'];
            $time = $cookie['time'];
            //混淆加密字符
            $key = $config['cookie']['hash_key'] ?? '123456';
            //加密值
            $hash = hash_hmac("sha1", $value, $key);
            //保存位置
            $path = $config['cookie']['path'] ?? "/";
            $client->cookie = $client->cookie ?? [];
            $client->cookie[$name] = $value;
            $client->cookie[$name."_hash"] = $hash;
            //每个域名都保存一次
            foreach ($domains as $domain){
                if($this->swoole_response){
                    $this->swoole_response->cookie($name, $value, time() + $time, $path, $domain, $config['cookie']['secure'], $config['cookie']['http_only']);
                    $this->swoole_response->cookie($name."_hash", $hash, time() + $time, $path, $domain, $config['cookie']['secure'], $config['cookie']['http_only']);
                }else{
                    setcookie($name, $value, time() + $time, $path, $domain, $config['cookie']['secure'], $config['cookie']['http_only']);
                    setcookie($name."_hash", $hash, time() + $time, $path, $domain, $config['cookie']['secure'], $config['cookie']['http_only']);
                }
            }
        }
    }

    /**
     * 移除cookie
     * @param $name
     * @return Response
     */
    public function withoutCookie(string $name){
        return $this->withCookie($name, null, -1);
    }

    /**
     * 往客户端输出状态
     * @param int $code
     * @return Response
     */
    public function withStatus(int $code){
        $this->status_code = $code;
        return $this;
    }

    /**
     * 输出JSON
     * @param $object
     * @return Response
     */
    public function withJson($object){
        return $this->withHeader("Content-Type", "application/json")->withContent($object);
    }

    /**
     * 输出HTML
     * @param $HTML
     * @return Response
     */
    public function withHTML($HTML){
        return $this->withHeader("Content-Type", "text/html")->withContent($HTML);
    }

    /**
     * 设置content
     * @param $content
     * @return Response
     */
    public function withContent($content){
        $this->content = is_string($content) ? $content : (is_array($content) || is_object($content) ? json_encode($content, JSON_UNESCAPED_UNICODE) : $content);
        return $this;
    }

    /**
     * 输出文本
     * @param string $text
     * @return Response
     */
    public function withText(string $text){
        return $this->withHeader("Content-Type", "text/plain")->withContent($text);
    }

    /**
     * 设置编码
     * @param string $charset
     * @return Response
     */
    public function setChar($charset = "utf-8"){
        $this->charset = $charset;
        return $this;
    }

    /**
     * 设置http版本
     * @param string $version
     * @return Response
     */
    public function version($version = "1.1"){
        $this->version = $version=="2.0" || $version==2 ? "2.0" : "1.1";
        return $this;
    }

    /**
     * 向客户端发送结果数据
     */
    public function send(){
        if($this->hasSend){
           return;
        }
        //处理cookies
        if(!empty($this->cookies)){
            $this->sendCookies();
        }
        //加入跨域
        //查找是否配置有独立域名
        if(!empty($this->path)){
            $this->accessOriginProcess($this->path);
        }
        //加入编码
        $this->withAddHeader("Content-Type", "charset={$this->charset}");
        //处理headers
        if(!empty($this->headers)){
            foreach ($this->headers as $name=>$header){
                $this->header($name, implode(";", $header));
            }
        }
        $this->end($this->content ?? '');
        $this->hasSend = true;
        if(Context::isSwoole()){
            //swoole 服务，不可 exit，退出当前协程？？

        }else{
            //fast cgi 或 php-fpm 到此结束
            exit();
        }
    }

    /**
     * 响应结束
     * @param string $content
     */
    public function end(string $content)
    {
        // TODO: Implement end() method.
        if($this->swoole_response){
            $this->swoole_response->end($content);
        }else{
            echo $content;
        }
    }

    /**
     * 处理跨域
     * @param $path
     * @return bool
     */
    public function accessOriginProcess($path){
        $config = Config::get('http');
        $client = Context::getClient();
        $router = $config['router'];
        $host = $client->host;
        $origin = $client->origin ?? $host;
        if($host != $origin){
            //不同的域名，需要判断是否在白名单中，黑名单在HttpWorker已处理，黑名单的IP、域名是进不来这里的
            $access_origin = $router['access_origin'] ?? [];
            if(isset($access_origin[$path])){
                if(is_array($access_origin[$path])){
                    if(in_array($host, $access_origin[$path])){
                        $this->agreeHost($host);
                    }else{
                        return false;
                    }
                }elseif($access_origin[$path] == "*"){
                    $this->agreeHost("*");
                }
            }
        }
        return true;
    }

    /**
     * 允许跨域域名
     * @param $host
     * @return Response
     */
    public function accessOrigin($host){
        return $this->withHeader("Access-Control-Allow-Origin", $host);
    }

    /**
     * 允许跨域头部请求
     * @param $header
     * @return Response
     */
    public function accessHeader($header){
        return $this->withHeader("Access-Control-Allow-Headers", $header);
    }

    /**
     * 允许跨域方法
     * @param $methods
     * @return Response
     */
    public function accessMethods($methods){
        return $this->withHeader("Access-Control-Allow-Methods", $methods);
    }

    /**
     * 跨域绿色通道
     * @param $host
     * @return Response
     */
    public function agreeHost($host){
        return $this->accessOrigin($host)
            ->accessHeader("Content-Type")
            ->accessHeader("X-Requested-With")
            ->accessMethods("GET,PUT,DELETE,POST,OPTIONS");
    }

    /**
     * 直接输出状态码
     * @param int $code
     */
    public function sendStatus(int $code){
        $this->withHeader("Content-Type", "charset={$this->charset}")->withStatus($code)->send();
    }

    /**
     * 处理OPTIONS请求
     * @return Response
     */
    public function processOptions(){
        $this->withHeader("Access-Control-Allow-Origin", "*");
        $this->withHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS');
        $this->withHeader("Access-Control-Allow-Headers", "X-Requested-With, Content-Type, Access-Control-Allow-Origin, Access-Control-Allow-Headers, X-Requested-By, Access-Control-Allow-Methods");
        return $this;
    }

    /**
     * 重定向
     * @param string $uri
     * @param int $status
     */
    public function redirect(string $uri, $status = 302){
        if($this->swoole_response){
            $this->swoole_response->redirect($uri, $status);
        }else{
            $this->withStatus($status);
            $this->withHeader("Location", $uri)->send();
        }
    }
}