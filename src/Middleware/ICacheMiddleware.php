<?php
/**
 * Create By Hunter
 * 2020/12/1 10:30 上午
 *
 */
namespace Inphp\Service\Middleware;

interface ICacheMiddleware
{
    public function get($name, $value);
}