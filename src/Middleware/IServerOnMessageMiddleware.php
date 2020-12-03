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
 * websocket 收到客户端消息
 * Interface IServerOnMessageMiddleware
 * @package Inphp\Service\Middleware
 */
interface IServerOnMessageMiddleware
{
    /**
     * @param $server
     * @param $frame
     * @return mixed
     */
    public function process($server, $frame);
}