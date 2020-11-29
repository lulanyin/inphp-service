<?php
namespace Inphp\Service\Middleware;

/**
 * 客户端请求中间键接口类
 * 这算是 request 的第 1 个中间键，除了 onConnect 或 onOpen
 * request请求时，会获取一些数据，保存到当前上下文中，然后会遍历执行用户配置的中间键
 * Interface IRequestMiddleware
 * @package Inphp\Service\Middleware
 */
interface IServerOnRequestMiddleware
{
    public function process($server, $request, $response);
}
