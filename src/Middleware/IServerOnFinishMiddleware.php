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
 * 异步任务完成回调
 * Interface IServerFinishMiddleware
 * @package Inphp\Service\Middleware
 */
interface IServerOnFinishMiddleware
{
    /**
     * @param $server
     * @param int $task_id
     * @param mixed $data
     * @return mixed
     */
    public function process($server, int $task_id, $data);
}