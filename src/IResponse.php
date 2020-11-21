<?php
namespace Small\Service;

/**
 * 响应接口
 * Interface IResponse
 * @package Small\Service
 */
interface IResponse
{
    /**
     * 发送数据给客户端
     * @return mixed
     */
    public function send();
}