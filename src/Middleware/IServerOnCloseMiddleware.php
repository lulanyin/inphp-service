<?php
/**
 * Create By Hunter
 * 2020/12/1 2:45 下午
 *
 */
namespace Inphp\Service\Middleware;

interface IServerOnCloseMiddleware
{
    public function process($server, $fd, $reactor_id);
}