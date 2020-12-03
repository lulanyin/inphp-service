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
 * 进程启动
 * Interface IServerOnWorkerStartMiddleware
 * @package Inphp\Service\Middleware
 */
interface IServerOnWorkerStartMiddleware
{
    public function process($server, $worker_id);
}