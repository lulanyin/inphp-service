<?php
namespace Inphp\Service\Middleware;

interface IServerOnStartMiddleware
{
    public function process($server);
}