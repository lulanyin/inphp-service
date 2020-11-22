<?php
namespace Small\Service;

/**
 * 服务中间件
 * Interface IMiddleWare
 * @package Small\Service
 */
interface IMiddleWare
{
    public static function process(&$response = null, &$controller = null, string $method = null);
}