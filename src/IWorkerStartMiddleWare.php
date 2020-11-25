<?php
/**
 * Create By Hunter
 * 2020/11/25 12:24 下午
 *
 */
namespace Inphp\Service;

use Swoole\Http\Server;

interface IWorkerStartMiddleWare
{
    public function process(Server $server, int $worker_id);
}