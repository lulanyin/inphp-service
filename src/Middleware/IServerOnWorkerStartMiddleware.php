<?php
namespace Inphp\Service\Middleware;

interface IServerOnWorkerStartMiddleware
{
    public function process($server, $worker_id);
}