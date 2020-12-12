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

use Inphp\Service\Middleware\IRouterMiddleware;
use Inphp\Service\Object\Status;

/**
 * 路由
 * Class Router
 * @package Inphp\Service
 */
class Router
{
    /**
     * 保存路由记录
     * @var array
     */
    public static $list = [];

    /**
     * 获取路由
     * @param $host
     * @param $uri
     * @param string $group
     * @return mixed|null
     */
    public static function get($host, $uri){
        $uri = '' ? '/' : $uri;
        $hash = md5($host.$uri);
        return self::$list[$hash] ?? null;
    }

    /**
     * 设置路由
     * @param $host
     * @param $uri
     * @param $status
     * @param string $group
     */
    public static function set($host, $uri, $status){
        $uri = '' ? '/' : $uri;
        $hash = md5($host.$uri);
        self::$list[$hash] = $status;
    }

    /**
     * 清除缓存
     */
    public static function clear(){
        self::$list = [];
    }

    /**
     * 处理uri, 获得路由状态数据
     * @param string $uri
     * @param string $request_method
     * @param string $group
     * @return Status
     */
    public static function process(string $uri = '', string $request_method = 'GET', string $group = Service::HTTP){
        //获取客户端数据
        $client = Context::getClient(Context::getClientId());
        //处理...
        $path = $uri;
        //处理地址参数
        if(stripos($path, "?")>0){
            $path = substr($path, 0, stripos($path, "?"));
        }
        //清除多余斜杠
        $path = str_replace("\\", "/", $path);
        $path = str_replace("//", "/", $path);
        //删除首个斜杠
        $path = stripos($path, "/")===0 ? substr($path, 1) : $path;
        //删除结尾的斜杠
        if(strrchr($path, "/")=="/"){
            $path = substr($path, 0, -1);
        }
        //过滤完
        $end_uri = $path;

        //谷歌浏览器的 favicon.ico 请求
        if($path == 'favicon.ico'){
            //
            return new Status([
                "status"        => 200,
                "message"       => "ok",
                "state"         => 'favicon.ico',
                "controller"    => null,
                "method"        => null,
                "view"          => null,
                "uri"           => $end_uri
            ]);
        }
        //拦截一些请求
        if($request_method == 'OPTIONS' || $request_method == 'DELETE' || $request_method == 'PUT'){
            return new Status([
                "status"        => 200,
                "message"       => "ok",
                "state"         => $request_method,
                "controller"    => null,
                "method"        => null,
                "view"          => null,
                "uri"           => $end_uri
            ]);
        }
        //在已设置的路由中找
        $cache_status = self::get($client->host, $end_uri);
        if(!empty($cache_status)){
            return $cache_status;
        }
        //中间键
        $middleware_list = Config::get($group.'.middleware.on_router', []);
        $middleware_list = is_array($middleware_list) ? $middleware_list : [];
        foreach ($middleware_list as $middleware){
            $_res = null;
            if(is_array($middleware)){
                //[__class__, 'static method']
                $_class = $middleware[0];
                $_method = $middleware[1] ?? null;
                if(class_exists($_class) && !empty($_method)){
                    $_res = call_user_func_array([$_class, $_method], [$client->host, $end_uri, $request_method, $group]);
                }
            }elseif(is_string($middleware) && class_exists($middleware)){
                $m = new $middleware();
                if($m instanceof IRouterMiddleware){
                    $_res = $m->process($client->host, $end_uri, $request_method, $group);
                }
            }elseif($middleware instanceof \Closure){
                $_res = call_user_func($middleware, [$client->host, $end_uri, $request_method, $group]);
            }
            //如果已处理到状态数据，则直接返回，下方不再处理
            if($_res instanceof Status){
                if($_res->status == 200){
                    self::set($client->host, $end_uri, $_res);
                }
                return $_res;
            }
        }

        /**
         * 下方判断的逻辑：（可能以后我也不懂我为何要这么写.....）
         * 1. 优先使用命名空间入口，进入PHP处理
         * /list : /list->index()    /user/list : /user->list()
         * 2. (HTTP)如果根据请求路径，未找到入口，则使用首页的入口，同时，根据请求路径，查看是否存在对应的静态文件
         * 如果存在，则展示此静态文件，其实直接识别静态文件，是存在风险的，所以请勿在静态文件上留下有风险的代码，默认按内容显示，不执行PHP代码。
         * /list : /list.html  /user/list : /user/list.html
         * 3. 上方2个方式都未找到匹配，则进入智能匹配：
         * /list : /index->list()  /user/list : user/index->list()
         * 4. 都找不到，则会返回 404 状态码
         * ---------------
         * 如果匹配域名，则路径会是 /{匹配的域名入口}/{请求路径}  :  api.xxx.com/user/list 识别为  /api/user/list
         * 如果未匹配域名，则路径是 /{请求路径} : www.xxx.com/api/user/list 识别为 /api/user/list， www.xxx.com/news/list 识别为 /web/news/list
         */

        //获取配置
        $configs = Config::get($group);

        //路由配置
        $router = $configs['router'];
        //定义的独立域名
        $router_domains = $router['domains'] ?? [];
        //当前请求域名
        $host = $client->host;
        //如果配置的独立域名，判断请求域名是否在独立域名列表中，如果存在，则获取对应的路由入口
        if(is_array($router_domains) && ($name = array_search($host, $router_domains))){
            //找到域名的对应入口
            //拆分请求路径，默认为 /index/index : index->index()
            $pathArray = !empty($path) ? explode("/", $path) : [
                "index", "index"
            ];
            //至少为2长度 [class, ...method]
            if(count($pathArray)<2){
                //使用 index 填充 /class/index : /class->index()
                $pathArray = array_pad($pathArray, 2, "index");
            }
            //加入路由 [ router, class, ...method ]
            $pathArray = array_merge([$name], $pathArray);
        }else{
            //通过常规入口去查找
            $listPath = array_keys($router["list"]);
            //拆分请求路径，默认为 /public/index/index : /public/index->index();
            $pathArray = !empty($path) ? explode("/", $path) : [
                $router["default"], "index", "index"
            ];
            //判断第一截是否属于默认入口，如果不是，则使用使用网站的默认入口
            if(!in_array($pathArray[0], $listPath)){
                //添加默认入口前缀
                $pathArray = array_merge([$router["default"]], $pathArray);
            }
            //默认入口替换为正确的入口文件夹
            $pathArray[0] = $router["list"][$pathArray[0]];
            //如果长度少于3，则使用 index 填充满
            if(count($pathArray)<3){
                $pathArray = array_pad($pathArray, 3, "index");
            }
        }
        //入口前置
        $router_prefix = $configs['home'];
        $view_suffix = $view_dir = '';
        if($group == Service::HTTP) {
            $view_dir = $configs['view'];
            $view_dir = strrchr($view_dir, "/") == "/" ? $view_dir : "{$view_dir}/";
            $view_suffix = $configs['view_suffix'];
        }
        //获取响应数据类型
        $response_content_type = $router['response_content_type'] ?? [];
        $status = self::match($router_prefix, $pathArray, $response_content_type[reset($pathArray)] ?? null, $view_dir, $view_suffix, $group);
        $status->uri = $end_uri;
        //正常状态，保存到缓存
        if($status->status == 200){
            self::set($host, $end_uri, $status);
        }
        //返回状态
        return $status;
    }


    /**
     * 集中处理匹配路由状态数据
     * @param $home
     * @param $pathArray
     * @param string $response_content_type
     * @param string $view_dir
     * @param string $view_suffix
     * @param string $group
     * @return Status
     */
    public static function match($home, $pathArray, $response_content_type = 'default', $view_dir = __DIR__, $view_suffix = 'html', $group = Service::HTTP){
        //处理末尾字符
        $home = strrchr($home, "\\") == "\\" ? $home : "{$home}\\";
        //处理视图后缀
        $view_suffix = stripos($view_suffix, ".") === 0 ? $view_suffix : ".{$view_suffix}";
        $view_dir = strrchr($view_dir, "/") == "/" ? $view_dir : "{$view_dir}/";
        $view_dir = str_replace("//", "/", $view_dir);
        //默认使用最后一截字符作方法
        $method = end($pathArray);
        //截取处理 /api/pro/list -> /api/pro->list();
        $pathArray = array_slice($pathArray, 0, -1);
        //组合成类名
        $className = join("\\", $pathArray);
        //拼接成完整的类
        $controller = $c1 = $home.$className;
        //判断是否存在
        if(!class_exists($controller)){
            //使用全匹配
            $pathArray[] = $method;
            //方法默认为 index
            $method = "index";
            //处理为 /api/pro/list -> /api/pro/list
            $className = join("\\", $pathArray);
            $controller = $c2 = $home.$className;
            if(!class_exists($controller)){
                //未找到，优先匹配视图文件（仅HTTP）
                if($group == Service::HTTP && !stripos($response_content_type, 'json')){
                    //视图文件
                    $view_file = join("/", $pathArray);
                    $view_file = strrchr($view_file, $view_suffix) === $view_suffix ? $view_file : "{$view_file}{$view_suffix}";
                    $slice = false;
                    if(!file_exists($view_dir.$view_file)){
                        $slice = true;
                        $view_file = join("/", array_slice($pathArray, 0, -1));
                        $view_file = strrchr($view_file, $view_suffix) === $view_suffix ? $view_file : "{$view_file}{$view_suffix}";
                    }
                    if(file_exists($view_dir.$view_file)){
                        $pathArray = $slice ? array_slice($pathArray, 0, -1) : $pathArray;
                        //匹配到视图文件
                        $path = reset($pathArray);
                        $className = join("\\", [$path, "index"]);
                        $controller = $home.$className;
                        return new Status([
                            "status"    => 200,
                            "state"     => 'html',
                            "controller"=> class_exists($controller) ? $controller : null,
                            "method"    => class_exists($controller) ? "index" : null,
                            "view_dir"  => $view_dir.$path,
                            "path"      => $path,
                            "view"      => substr($view_file, strlen($path) + 1)
                        ]);
                    }
                }
                //进行智能匹配
                //首先匹配：/api/pro/list -> /api/pro/list/index->index();
                $pathArray[] = "index";
                //使用默认方法
                $method = "index";
                $className = join("\\", $pathArray);
                $controller = $c3 = $home.$className;
                if(!class_exists($controller)){
                    // /api/pro/list/index 恢复为 /api/pro/list
                    $pathArray = array_slice($pathArray, 0, -1);
                    if(count($pathArray) == 3 && end($pathArray) == "index"){
                        //可能是 智路径， 先恢复 如：/web/list 会处理为  /web/list/index， 由于 /web/list 默认已处理过了， 这里可以处理为 /web/index->list()
                        $method = $pathArray[1];
                        $pathArray = [$pathArray[0], "index"];
                    }else{
                        //取 list 为方法
                        $method = end($pathArray);
                        //使用 index
                        $pathArray[count($pathArray) - 1] = "index";
                    }
                    $className = join("\\", $pathArray);
                    $controller = $c4 = $home.$className;
                    if(!class_exists($controller)){
                        //找不到....啥也找不到....
                        return  new Status([
                            "status"    => 404,
                            "message"       => "class [{$c1}, {$c2}, {$c3}, {$c4}] not exists!",
                            "response_content_type" => $response_content_type
                        ]);
                    }
                }
            }
        }

        //若找到了控制器，则进行类判断
        try{
            //使用反射类，查看方法是否是公共方法，否则也无法使用，如果方法不存在，则也无法使用
            $mr = new \ReflectionMethod($controller, $method);
            $modifierNames = \Reflection::getModifierNames($mr->getModifiers());
            //判断是不是公开的方法
            if(strtolower($modifierNames[0]) != "public"){
                throw new \ReflectionException("method [{$controller}->{$method}()] not public!", 404);
            }
            if($group == Service::HTTP && !stripos($response_content_type, 'json')){
                $view_file = join("/", array_slice($pathArray, 1));
                $view_file = strrchr($view_file, $view_suffix) === $view_suffix ? $view_file : "{$view_file}{$view_suffix}";
            }
            $path = reset($pathArray);
            return new Status([
                "status"        => 200,
                "message"       => "ok",
                "state"         => "controller",
                "controller"    => $controller,
                "method"        => $method,
                "view_dir"      => $view_dir.$path,
                "path"          => $path,
                "view"          => $view_file ?? null,
                "response_content_type" => $response_content_type
            ]);
        }catch (\ReflectionException $exception){
            return new Status([
                "status"    => 404,
                "message"   => $exception->getMessage(),
                "response_content_type" => $response_content_type
            ]);
        }
    }
}