# PHP 应用服务

### 配置
```php
//定义常量，此常量可以是文件路径，也可以直接是值
define("SERVICE_CONFIG", ROOT."/config/service.php");

//ROOT."/config/service.php"
return [
    //debug 调试是否打开
    "debug" => true,
    //服务总开关，如果关闭，所有服务不可用
    "enable"=> true,
    //路由
    "router"=> [
        //http 路由
        "http"      => [
            //入口前缀位置，使用命名空间
            "home"  => "app\http\\\\",
            //默认域名打开进入的位置，对应下方 list 的一个 key
            "default"   => "public",
            //路由列表，仅对应第一层文件夹，key 替换为地址的值， value 为访问入口的命名空间，请勿重复，并且请勿使用 PHP 系统变量、关键字
            "list"  => [
                //默认访问位置
                "public"    => "web",
                //后台，可修改 key 伪装地址
                "admin@md5" => "admin",
                //API接口
                "api"       => "api"
            ]
        ]
    ],
    //swoole配置，详细请查看 https://swoole.com
    "swoole" => [
        //http服务
        "http"      => [
            //绑定IP
            "ip"    => "0.0.0.0",
            //监听端口
            "port"  => 1990,
            //swoole http server 配置
            "settings"  => [
                //PID文件保存位置
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
                'log_file'              => ROOT.'/runtime/log/http_service.log',
                //默认异步进程数量
                'task_worker_num'       => 0,
                'package_max_length'    => 8092,
                'upload_tmp_dir'        => ROOT.'/runtime/upload',
                'document_root'         => ROOT.'/public',
                'upload_dir'            => ROOT.'/public/attachment'
            ]
        ]
    ]
];
```

#### Http

#### WebSocket