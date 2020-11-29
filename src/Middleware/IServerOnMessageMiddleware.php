<?php
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