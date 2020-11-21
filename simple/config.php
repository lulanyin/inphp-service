<?php
require_once "../vendor/autoload.php";

//时区
date_default_timezone_set("PRC");
//文件夹分隔符
!defined("DS") && define("DS", DIRECTORY_SEPARATOR);
//根目录
define("ROOT",              __DIR__);
define("BASE_PATH",         ROOT);

//定义服务名称
define("SWOOLE", "swoole");
define("FPM", "php-fpm");

//默认使用PHP-FPM，此处请勿设置此常量，在服务入口设置常量
//define("SERVICE_PROVIDER", FPM);
