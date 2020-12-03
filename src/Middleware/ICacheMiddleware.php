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
 * 缓存中间键
 * Interface ICacheMiddleware
 * @package Inphp\Service\Middleware
 */
interface ICacheMiddleware
{
    /**
     * 获取
     * @param string $name
     * @param $default
     * @return mixed
     */
    public function get(string $name, $default);

    /**
     * 设置
     * @param string $name
     * @param $value
     * @return mixed
     */
    public function set(string $name, $value);

    /**
     * 移除
     * @param string $name
     * @return mixed
     */
    public function remove(string $name);

    /**
     * 清除
     * @return mixed
     */
    public function clean();
}