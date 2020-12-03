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
 * 客户端断开连接
 * Interface IServerOnCloseMiddleware
 * @package Inphp\Service\Middleware
 */
interface IServerOnCloseMiddleware
{
    public function process($server, $fd, $reactor_id);
}