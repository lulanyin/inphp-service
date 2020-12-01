<?php
/**
 * Create By Hunter
 * 2020/12/1 11:29 上午
 *
 */
namespace Inphp\Service\Middleware;

interface IServerOnOpenMiddleware
{
    public function process($server, $request);
}