<?php
return [
    //http 配置
    'http'  => [
        //server 类型(仅在swoole http server有效)
        'sock_type'      => defined('SWOOLE_SOCK_TCP') ? SWOOLE_SOCK_TCP : null,
        //绑定IP(仅在swoole http server有效)
        'ip'        => '0.0.0.0',
        //绑定端口(仅在swoole http server有效)
        'port'      => 1990,
        //服务配置(仅在swoole http server有效)
        'settings'  => [
            //swoole\http\server 的配置，已默认一些配置，可重写
            //PID文件保存位置，文件夹必须存在
            'pid_file'              => RUNTIME.'/http_service.pid',
            //worker 数量，一般按CPU核心数量 * 2
            'worker_num'            => 2,
            //最大请求数量，按需，不可超过系统设置
            'max_request'           => 128,
            //最大连接数量
            'max_connection'        => 256,
            //日志文件，文件夹必须存在
            'log_file'              => RUNTIME.'/http_service.log',
            //默认异步进程数量
            'task_worker_num'       => 2,
            'package_max_length'    => 8092,
            'upload_tmp_dir'        => RUNTIME.'/upload',
            //默认静态文件目录，文件夹必须存在，一般使用nginx代理完成静态文件访问
            'document_root'         => ROOT.'/public',
            //文件上传保存文件夹
            'upload_dir'            => ROOT.'/public/attachment'
        ],
        //是否开启热更新
        'hot_update'   => [
            //是否打开
            'enable'    => true,
            //热更新监听的文件夹
            'listen_dir'=> [
                APP_PATH."/http",
                VIEW
            ],
            //热更新版本缓存文件
            'version_file' => RUNTIME."/http_version.txt",
            //热更新间隔时间
            'seconds'   => 10
        ],
        //http入口
        'home'      => 'Inphp\ServiceSimple\app\http\\',
        //视图文件
        'view'      => VIEW,
        //视图文件后缀，请勿带 .
        'view_suffix' => 'html',
        //视图是否允许执行PHP
        'view_php'  => false,
        //路由
        'router'    => [
            //默认访问位置 {home}\{router.default}，值对应下方 list 的 key
            'default'   => 'public',
            //列表， key 值是地址路径， value 是文件夹名称， 位于 {home} 下级文件夹
            'list'      => [
                //网站默认访问位置
                'public'    => 'web',
                //后台
                'adm@xyz'   => 'admin',
                //API接口
                'api'       => 'api'
            ],
            //自定义响应数据类型，默认是以控制器为准，其次是视图，然后可自定义为 json 或 application/json
            //JSON统一格式 { error : 0, message : 'success', data : '您响应的数据' }
            'response_content_type' => [
                //API接口的响应数据类型是JSON
                'api'   => 'application/json'
            ],
            //可配置独立域名，key 对应上方 list 的 value，值是独立域名，会自动识别使用该域名访问时，默认的访问入口。
            'domains'   => [
                'api'   => 'api-service.inphp.in'
            ],
            //跨域
            'access_origin' => [
                //API支持任何跨域
                'api'   => '*'
            ]
        ],
        //中间键 或 回调
        'middleware'    => [
            //------------------------------ 服务
            //服务启动前
            'before_start'  => [

            ],
            //服务启动(仅在swoole http server有效)
            'on_start'         => [],
            //子进程启动(仅在swoole http server有效)
            'on_worker_start'  => [],
            //------------------------------ 请求部分
            //接收到请求
            'on_request'    => [
                //继承
                \Inphp\ServiceSimple\app\middleware\middle::class,
                //直接函数
                function(){
                    echo 'fun before_start'.PHP_EOL;
                },
                //静态方法
                [\Inphp\ServiceSimple\app\middleware\middle::class, 'static_process']
            ],
            //路由处理
            'on_router'     => [],
            //控制器已初始化，但未执行前
            'before_execute'=> [],
            //控制器已执行，未响应前
            'before_send'   => [],
            //----------------------------- 异步投递
            //投递异步任务(仅在swoole http server有效)
            'on_task'       => [],
            //异步任务执行完成(仅在swoole http server有效)
            'on_finish'     => []
        ],
        //cookie
        'cookie'    => [
            //cookie加密字符，默认值是123456，使用sha1
            'hash_key'  => '1v3r5a',
            //位置
            'path'      => '/',
            //https
            'secure'    => false,
            //http only
            'http_only' => true,
            //共享域名 xx.a.com  请勿添加 http:// 或 https:// 开头
            'domains'   => []
        ],
        //session， 该配置仅对 swoole http server 有效，因为swoole无法像PHP-FPM一样使用 $_SESSION
        'session'   => [
            //所用驱动，默认使用文件系统，如果使用其它，请填写 middleware，并实现它
            'driver'    => 'file',
            'path'      => RUNTIME.'/session',
            'middleware'=> [
                'get'   => null,
                'set'   => null,
                'drop'  => null
            ]
        ]
    ],
    //swoole websocket 配置
    'ws'    => [
        //server 类型
        'sock_type'      => defined('SWOOLE_SOCK_TCP') ? SWOOLE_SOCK_TCP : null,
        //绑定IP
        'ip'        => '0.0.0.0',
        //绑定端口
        'port'      => 1991,
        //服务配置(仅在swoole http server有效)
        'settings'  => [
            //swoole\http\server 的配置，已默认一些配置，可重写
            //PID文件保存位置，文件夹必须存在
            'pid_file'              => RUNTIME.'/ws_service.pid',
            //worker 数量，一般按CPU核心数量 * 2
            'worker_num'            => 2,
            //最大请求数量，按需，不可超过系统设置
            'max_request'           => 128,
            //最大连接数量
            'max_connection'        => 256,
            //日志文件，文件夹必须存在
            'log_file'              => RUNTIME.'/ws_service.log',
            //默认异步进程数量
            'task_worker_num'       => 2,
            'package_max_length'    => 8092
        ],
        //是否开启热更新
        'hot_update'   => [
            //是否打开
            'enable'    => true,
            //热更新监听的文件夹
            'listen_dir'=> [
                APP_PATH."/ws"
            ],
            //热更新版本缓存文件
            'version_file' => RUNTIME."/ws_version.txt",
            //热更新间隔时间
            'seconds'   => 10
        ],
        //ws入口
        'home'      => 'Inphp\ServiceSimple\app\ws\\',
        //路由
        'router'    => [
            //默认访问位置 {home}\{router.default}，值对应下方 list 的 key
            'default'   => 'public',
            //列表， key 值是地址路径， value 是文件夹名称， 位于 {home} 下级文件夹
            'list'      => [
                //默认消息接收
                'public'    => 'web',
            ]
        ],
        //中间键 或 回调
        'middleware'    => [
            //------------------------------ 服务
            //服务启动前
            'before_start'  => [

            ],
            //服务启动(仅在swoole http server有效)
            'on_start'         => [],
            //子进程启动(仅在swoole http server有效)
            'on_worker_start'  => [],
            //------------------------------ 消息部分
            //新连接
            'on_open'       => [],
            //关闭
            'on_close'      => [],
            //收到客户端消息
            'on_message'    => [
                //直接函数
                function(){
                    echo 'fun on_request'.PHP_EOL;
                }
            ],
            //路由处理
            'on_router'     => [],
            //控制器已初始化，但未执行前
            'before_execute'=> [],
            //向客户端发送消息之前
            'before_send'   => [],
            //----------------------------- 异步投递
            //投递异步任务(仅在swoole http server有效)
            'on_task'       => [],
            //异步任务执行完成(仅在swoole http server有效)
            'on_finish'     => []
        ]
    ]
];