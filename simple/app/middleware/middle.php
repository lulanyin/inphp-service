<?php
namespace Inphp\ServiceSimple\app\middleware;

use Inphp\Service\Http\Response;
use Inphp\Service\Http\Server;
use Inphp\Service\Middleware\IServerOnRequestMiddleware;

class middle implements IServerOnRequestMiddleware
{
    public function process(Server $server, $request, Response $response)
    {
        // TODO: Implement process() method.
        echo 'IServerOnRequestMiddleware request'.PHP_EOL;
    }

    public static function static_process(){
        echo 'static request'.PHP_EOL;
    }
}