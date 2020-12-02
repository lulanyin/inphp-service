<?php
namespace Inphp\Service\Middleware;

use Inphp\Service\Object\Status;
use Inphp\Service\Service;

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
     * @param string $host
     * @param string $uri
     * @param string|null $method
     * @param string $group
     * @return Status|mixed
     */
    public function process(string $host, string $uri = '', string $method = null, $group = Service::HTTP);
}