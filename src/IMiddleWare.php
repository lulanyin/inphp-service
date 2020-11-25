<?php
namespace Inphp\Service;

use Inphp\Service\Http\Response;

/**
 * 服务中间件
 * Interface IMiddleWare
 * @package Small\Service
 */
interface IMiddleWare
{
    public function process(Response $response, $controller = null, string $method = null);
}