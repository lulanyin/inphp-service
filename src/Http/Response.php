<?php
namespace Inphp\Service\Http;

use Inphp\Service\IMiddleWare;
use Inphp\Service\IResponse;
use Inphp\Service\Object\Cookie;
use Inphp\Service\Object\Status;

class Response implements IResponse
{
    /**
     * @var \Swoole\Http\Response
     */
    private $swoole_response = null;

    /**
     * cookies
     * @var Cookie[]
     */
    private $cookies = [];

    /**
     * 编码
     * @var string
     */
    private $charset = "UTF-8";

    /**
     * header
     * @var array
     */
    private $headers = [];

    /**
     * 主体内容
     * @var null
     */
    private $content = null;

    /**
     * http状态码
     * @var int
     */
    private $status_code = 200;

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
    private $path = '';

    /**
     * 是否已发送过响应
     * @var bool 
     */
    private $hasSend = false;

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

        $controller = null;
        if($status->status == 200){
            if($status->state == 'options' || $status->state == 'favicon.ico'){
                return $this->processOptions();
            }
            if(!empty($status->controller)){
                //控制器
                $controllerName = $status->controller;
                $controller = new $controllerName();
            }
        }

        //中间键
        $config = Container::getConfig();
        $middleware = $config['router']['http']['middleware'] ?? [];
        //控制器执行前处理中间键
        $before_execute_middlewares = $middleware['before_execute'] ?? [];
        $before_execute_middlewares = is_string($before_execute_middlewares) ? [$before_execute_middlewares] : (is_array($before_execute_middlewares) ? $before_execute_middlewares : []);
        if(!empty($before_execute_middlewares)){
            foreach ($before_execute_middlewares as $before_execute_middleware){
                if($before_execute_middleware instanceof IMiddleWare){
                    $before_execute_middleware::process($this, $controller, $status->method);
                }
            }
        }
        //响应
        if(!is_null($controller)){
            if(method_exists($controller, $status->method)){
                $result = $controller->{$status->method}();
                if($result instanceof Response){
                    //$result->send();
                }elseif (is_string($result)){
                    $this->withAddHeader("Content-Type", "text/plain")->withContent($result);
                }elseif(is_object($result) || is_array($result)){
                    $this->withJson($result);
                }elseif(!empty($this->content)){
                    //已经设置有内容
                    //$this->send();
                }else{
                    
                }
            }
        }

        //发送前处理中间键
        $before_send_middlewares = $middleware['before_send'] ?? [];
        $before_send_middlewares = is_string($before_send_middlewares) ? [$before_send_middlewares] : (is_array($before_send_middlewares) ? $before_send_middlewares : []);
        if(!empty($before_send_middlewares)){
            foreach ($before_send_middlewares as $before_send_middleware){
                if($before_send_middleware instanceof IMiddleWare){
                    $before_send_middleware::process($this, $controller, $status->method);
                }
            }
        }

        //可以使用中间键完成模板渲染，如果都未处理，则默认使用PHP去处理视图文件
        if(empty($this->content) && file_exists($status->view) && $status->status == 200){
            //还未设置响应内容，默认展示模板
            $view_php = $config['router']['http']['view_php'] ?? false;
            if($view_php){
                //允许执行PHP
                ob_start();
                include $status->view;
                $this->withHTML(ob_get_contents());
                ob_clean();
            }else{
                //不执行PHP，按文件内容展示
                $this->withHTML(file_get_contents($status->view));
            }
        }
        //返回本身
        return $this;
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
     * @param $name
     * @param $value
     * @param int $time
     * @return Response
     */
    public function withCookie(string $name, string $value, $time = 3600){
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
     * @param string $value
     * @param int $time
     * @return Response
     */
    public function cookie(string $name, string $value, int $time = 3600){
        return $this->withCookie($name, $value, $time);
    }

    /**
     * 输出cookie
     */
    public function sendCookies(){
        $config = Container::getConfig();
        //获取客户端
        $client = Container::getClient();
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
            $key = $config['cookie']['hash_key'] ?? '1a2b3c5g';
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
     * @param $text
     * @return Response
     */
    public function withText($text){
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
        $config = Container::getConfig();
        $client = Container::getClient();
        $router = $config['router'];
        $host = $client->host;
        $origin = $client->origin ?? $host;
        if($host != $origin){
            //不同的域名，需要判断是否在白名单中，黑名单在HttpWorker已处理，黑名单的IP、域名是进不来这里的
            $access_origin = $router['http']['access_origin'] ?? [];
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
            exit;
        }
    }
}