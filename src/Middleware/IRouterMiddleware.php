<?php
namespace Inphp\Service\Middleware;

use Inphp\Service\Object\Status;

/**
 * 执行路由匹配的中间键
 * 按顺序，这是 在 request 之后的第 2 个中间键
 * 已处理请求的基础数据，
 * Interface IRouterMiddleware
 * @package Inphp\Service\Middleware
 */
interface IRouterMiddleware
{
    /**
     * 处理请求路径
     * @param string $uri
     * @param string|null $method
     * @param string $group
     * @return mixed
     */
    public function process(string $uri = '', string $method = null, $group = 'http');
}