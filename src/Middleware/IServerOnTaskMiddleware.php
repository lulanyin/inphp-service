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
 * Task 中间键
 * Interface IServerTaskMiddleware
 * @package Inphp\Service\Middleware
 */
interface IServerOnTaskMiddleware
{
    /**
     * 处理 task
     * @param $server
     * @param int $task_id
     * @param int $worker_id
     * @param $data
     * @return mixed
     */
    public function process($server, int $task_id, int $worker_id, $data);
}
