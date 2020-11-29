<?php
namespace Inphp\Service\Middleware;

/**
 * 服务启动前，要做什么
 * Interface IServerBeforeStartMiddleware
 * @package Inphp\Service\Middleware
 */
interface IServerBeforeStartMiddleware
{
    public function process();
}