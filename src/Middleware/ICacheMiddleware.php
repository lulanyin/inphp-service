<?php
/**
 * Create By Hunter
 * 2020/12/1 10:30 上午
 *
 */
namespace Inphp\Service\Middleware;

interface ICacheMiddleware
{
    public function get(string $name, $default);
    public function set(string $name, $value);
    public function remove(string $name);
    public function clean(string $name);
}