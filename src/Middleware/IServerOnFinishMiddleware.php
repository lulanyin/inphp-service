<?php
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