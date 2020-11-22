<?php
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
            "home"  => "SimpleService\app\http\\",
            //视图文件位置
            "view"  => ROOT."/resources/view/",
            //是否允许执行PHP
            "view_php" => false,
            //视图文件后缀
            "view_suffix" => "html",
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
            ],
            //响应数据格式，未定义的都按常规处理，会自动识别是AJAX请求，AJAX请求时，会使用JSON响应
            //JSON统一格式 { error : 0, message : 'success', data : { title : '...', content : '...' } }
            "response_content_type" => [
                //API响应，使用JSON格式，
                "api"   => "json"
            ],
            //配置独立域名，key 对应上方 list 的 value，值是独立域名，会自动识别使用该域名访问时，默认的访问入口。
            "domain" => [
                //"api"   => "api.xxx.com"
            ],
            //跨域，有其它需求，请使用中间键实现拦截IP、域名等等
            "access_origin" => [
                //API不限制域名请求
                "api"   => "*"
            ],
            //中间键，请实现 IMiddleWare 接口
            "middleware"    => [
                //请求开始处理之前，比如说，实现注解执行
                "before_request"    => [],
                //请求已处理，但在控制器未执行，比如说，处理控制器的注解
                "before_execute"    => [],
                //控制器已经执行，即将发送响应数据
                "before_send"       => []
            ]
        ]
    ],
    "cookie" => [
        //HTTPS
        "secure"   => true,
        //http only
        "http_only"=> true,
        //混淆加密字符串
        "hash_key" => "m34D1k5E",
        //保存路径
        "path"     => "/",
        //跨域共享，如果仅限当前站点域名，请勿填写
        "domains"  => []
    ],
    //swoole配置，详细请查看 https://swoole.com
    "swoole" => [
        //http服务
        "http"      => [
            //绑定IP
            "ip"    => "0.0.0.0",
            //监听端口
            "port"  => 1990,
            //是否自动重载服务（生产环境不建议开启，以免代码错误，造成服务中断）
            "auto_reload" => true,
            //监听文件夹，该文件夹下有任何文件变动，都会自动重载服务，以保证实时能更新程序
            "listen_dir" => [ROOT."/app", VIEW],
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
            ],
            //由于 swoole 服务无法提供像 PHP-FPM 一样的 session 数据，则需要另行实现， 如果有必要，可以使用中间键自行实现
            "session" => [
                //驱动，可选：file=临时文件，或 redis
                "driver"    => "file",
                //如果是使用临时文件，保存的位置在哪里？
                "file_path" => ROOT."/runtime/cache/session"
            ]
        ]
    ]
];