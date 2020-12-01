<?php
namespace Inphp\Service\Middleware;

use Inphp\Service\Http\Response;

/**
 * 路由处理完之后，就到响应类的中间键
 * 这应该算第 2 之后的中间键
 * 响应期间会有 2 次中间键的注入
 * 第 1 次是在控制器初始化，但未执行之前
 * 第 2 次是在控制器执行相应的方法之后，未响应数据给客户端之前
 * Interface IResponseMiddleware
 * @package Inphp\Service\Middleware
 */
interface IServerOnResponseMiddleware
{
    public function process(Response $response, $controller = null, $method = null);
}