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
namespace Inphp\Service;

/**
 * 响应接口
 * Interface IResponse
 * @package Small\Service
 */
interface IResponse
{
    public function cookie(string $name, string $value, int $time = 3600);
    public function header(string $name, string $value);
    public function end(string $content);
}