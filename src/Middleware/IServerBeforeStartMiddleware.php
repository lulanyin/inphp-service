<?php
// +----------------------------------------------------------------------
// | INPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2020 https://inphp.cc All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/MIT )
// +----------------------------------------------------------------------
// | Author: lulanyin <me@lanyin.lu>
// +----------------------------------------------------------------------
namespace Inphp\Service\Middleware;

/**
 * 服务启动前，要做什么
 * Interface IServerBeforeStartMiddleware
 * @package Inphp\Service\Middleware
 */
interface IServerBeforeStartMiddleware
{
    public function process($server);
}