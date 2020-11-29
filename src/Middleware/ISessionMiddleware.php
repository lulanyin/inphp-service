<?php
namespace Inphp\Service\Middleware;

interface ISessionMiddleware
{
    /**
     * 初始化，会传入 php_session_id
     * ISessionMiddleware constructor.
     * @param string $session_id
     */
    public function __construct(string $session_id);

    /**
     * 获取session，如果不传入name，则会返回全部的session
     * @param string|null $name
     * @param null $default
     * @return mixed
     */
    public function get(string $name = null, $default = null);

    /**
     * 设置Session值
     * @param string $name
     * @param null $value
     * @return mixed
     */
    public function set(string $name, $value = null);

    /**
     * 移除session
     * @param string $name
     * @return mixed
     */
    public function drop(string $name);
}