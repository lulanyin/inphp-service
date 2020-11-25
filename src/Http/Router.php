<?php
namespace Inphp\Service\Http;

use Inphp\Service\Object\Client;
use Inphp\Service\Object\Status;

/**
 * 路由处理器
 * Class Router
 * @package Inphp\Service\Http
 */
class Router
{
    /**
     * 配置
     * @var array
     */
    public $config = [];

    /**
     * 客户端数据
     * @var Client
     */
    public $client;

    /**
     * Router constructor.
     * @param Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client ?? Container::getClient();
        $this->config = Container::getConfig();
    }

    /**
     * 处理，并返回状态数据
     * @return Status
     */
    public function start(){
        //由客户端数据对象载入请求路径
        $path = $this->client->uri;
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

        //谷歌浏览器的 favicon.ico 请求
        if($path == 'favicon.ico'){
            //
            return new Status([
                "status"        => 200,
                "message"       => "ok",
                "state"         => 'favicon.ico',
                "controller"    => null,
                "method"        => null,
                "view"          => null
            ]);
        }

        //OPTIONS请求
        if($this->client->method == 'OPTIONS'){
            return new Status([
                "status"        => 200,
                "message"       => "ok",
                "state"         => 'options',
                "controller"    => null,
                "method"        => null,
                "view"          => null
            ]);
        }

        /**
         * 下方判断的逻辑：
         * 1. 优先使用命名空间入口，进入PHP处理
         * /list : /list->index()    /user/list : /user->list()
         * 2. 如果根据请求路径，未找到入口，则使用首页的入口，同时，根据请求路径，查看是否存在对应的静态文件
         * 如果存在，则展示此静态文件，其实直接识别静态文件，是存在风险的，所以请勿在静态文件上留下有风险的代码，默认按内容显示，不执行PHP代码。
         * /list : /list.html  /user/list : /user/list.html
         * 3. 上方2个方式都未找到匹配，则进入智能匹配：
         * /list : /index->list()  /user/list : user/index->list()
         * 4. 都找不到，则会返回 404 状态码
         * ---------------
         * 如果匹配域名，则路径会是 /{匹配的域名入口}/{请求路径}  :  api.xxx.com/user/list 识别为  /api/user/list
         * 如果未匹配域名，则路径是 /{请求路径} : www.xxx.com/api/user/list 识别为 /api/user/list， www.xxx.com/news/list 识别为 /web/news/list
         */

        //路由配置
        $router = $this->config['router']['http'];
        //定义的独立域名
        $router_domains = $router['domain'] ?? [];
        //当前请求域名
        $host = $this->client->host;
        //如果配置的独立域名，判断请求域名是否在独立域名列表中，如果存在，则获取对应的路由入口
        if(is_array($router_domains) && ($name = array_search($host, $router_domains))){
            //找到域名的对应入口
            //拆分请求路径，默认为 /index/index : index->index()
            $pathArray = !empty($path) ? explode("/", $path) : [
                "index", "index"
            ];
            //记录长度，后面智能匹配的时候要使用
            $pathLen = count($pathArray) + 1;
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
            //记录长度，后面智能匹配的时候要使用
            $pathLen = count($pathArray);
            //如果长度少于3，则使用 index 填充满
            if(count($pathArray)<3){
                $pathArray = array_pad($pathArray, 3, "index");
            }
        }
        //入口前置
        $router_prefix = $router['home'];
        $view_dir = $router['view'];
        $view_suffix = $router['view_suffix'];
        //筛选控制器，末尾的是 class的method，需要截断
        $className =  join("\\", array_slice($pathArray, 0, -1));
        $controller = $router_prefix.$className;
        if(!class_exists($controller)){
            //不存在，则查看静态文件是否存在，静态文件直接使用全路径匹配，不需要截断
            //别根目录静态文件需要特别处理，因为根目录的文件路径会是  /web/xxx.html/index -> /web/xxx.html/index.html  会处理为两条路径
            $html_file_one = join("/", $pathArray);
            $html_file_one = strrchr($html_file_one, ".{$view_suffix}") == ".{$view_suffix}" ? $html_file_one : "{$html_file_one}.{$view_suffix}";
            $html_file_two = null;
            if(count($pathArray) == 3){
                $html_file_two = join("/", array_slice($pathArray, 0, -1));
                $html_file_two = strrchr($html_file_two, ".{$view_suffix}") == ".{$view_suffix}" ? $html_file_two : "{$html_file_two}.{$view_suffix}";
            }
            // 判断两条路径 /web/xxx/xxx.html   /web/xxx.html
            if(file_exists($view_dir.$html_file_one) || (!is_null($html_file_two) && file_exists($view_dir.$html_file_two))){
                $className = join("\\", [reset($pathArray), "index"]);
                $controller = $router_prefix.$className;
                //找到静态文件
                return new Status([
                    "status"        => 200,
                    "message"       => "ok",
                    "state"         => Service::HTML,
                    "controller"    => class_exists($controller) ? $controller : null,
                    "method"        => "index",
                    "view"          => substr(file_exists($view_dir.$html_file_one) ? $html_file_one : $html_file_two, strlen(reset($pathArray))),
                    "path"          => reset($pathArray)
                ]);
            }else{
                //智能匹配
                $method = "index";
                //匹配全路径 /admin/user/list -> /admin/user/list->index();
                $controller = $c1 = $router_prefix.join("\\", $pathArray);
                if(!class_exists($controller)){
                    //匹配 /admin/user/list -> /admin/user/list/index->index();
                    $pathArray = array_merge($pathArray, ['index']);
                    $controller = $c2 = $router_prefix.join("\\", $pathArray);
                    if(!class_exists($controller)){
                        //匹配 /admin/user/list -> /admin/user/index->list(); 根据上边原始地址的 pathLen 来判断
                        //或者 /admin/list -> /admin/index->list()
                        $method = $pathLen <= 2 ? $pathArray[count($pathArray) - 3] : $pathArray[count($pathArray) - 2];
                        $pathArray = array_merge(array_slice($pathArray, 0, $pathLen<=2 ? -3 : -2), ["index"]);
                        $controller = $c3 = $router_prefix.join("\\", $pathArray);
                        if(!class_exists($controller)) {
                            //找不到，啥也找不到
                            return new Status([
                                "status"        => 404,
                                "message"       => "class [{$c1}, {$c2}, {$c3}] not exists!",
                                "state"         => Service::CONTROLLER,
                                "controller"    => null,
                                "method"        => null,
                                "view"          => null
                            ]);
                        }
                    }
                }
            }
        }else{
            //找到控制器，取出 method
            $method = end($pathArray);
            $pathArray = array_slice($pathArray, 0, -1);
        }
        $view_file = join("/", array_slice($pathArray, 1));
        $view_file = strrchr($view_file, ".{$view_suffix}") == ".{$view_suffix}" ? $view_file : "{$view_file}.{$view_suffix}";
        //进行类判断，使用反射方法
        try {
            //使用反射类，查看方法是否是公共方法，否则也无法使用，如果方法不存在，则也无法使用
            $mr = new \ReflectionMethod($controller, $method);
            $modifierNames = \Reflection::getModifierNames($mr->getModifiers());
            //判断是不是公开的方法
            if(strtolower($modifierNames[0]) != "public"){
                throw new \ReflectionException("method [{$controller}->{$method}()] not public!", 404);
            }
            //反射类，获取该类的方法列表，然后过滤掉继承类的方法，仅可使用自身的公共方法
            $reflection = new \ReflectionClass($controller);
            $methodList = $reflection->getMethods();
            $enableMethodList = [];
            foreach ($methodList as $item){
                if($item->class == $controller){
                    $enableMethodList[] = $item->name;
                }
            }
            if(!in_array($method, $enableMethodList)){
                throw new \ReflectionException("method [{$controller}->{$method}()] is parent's!", 404);
            }
            return new Status([
                "status"        => 200,
                "message"       => "ok",
                "state"         => Service::CONTROLLER,
                "controller"    => $controller,
                "method"        => $method,
                "view"          => $view_file,
                "path"          => reset($pathArray)
            ]);
        } catch (\ReflectionException $e) {
            return new Status([
                "status"        => 404,
                "message"       => $e->getMessage(),
                "state"         => Service::CONTROLLER,
                "controller"    => $controller,
                "method"        => $method,
                "view"          => $className,
                "path"          => reset($pathArray)
            ]);
        }
    }
}