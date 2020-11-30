<?php
/**
 * 此文件为最重要的文件，系统环境就靠这文件配置
 */
define('MAGIC_QUOTES_GPC', ini_set("magic_quotes_runtime",0) ? true : false);
//时区
date_default_timezone_set("PRC");
//文件夹分隔符
!defined("DS") && define("DS", DIRECTORY_SEPARATOR);
//根目录
define("ROOT", dirname(__DIR__));
define("BASE_PATH", ROOT);
//核心方法、类等文件存放的文件夹名
define("RESOURCE", ROOT."/resources");
//视图文件
define("VIEW", RESOURCE."/view");
//runtime目录
define("RUNTIME", ROOT."/runtime");
//app根目录
define("APP_PATH", ROOT."/app");
//站点配置
define("INPHP_SERVICE_CONFIG", __DIR__."/service.php");