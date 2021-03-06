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
namespace Inphp\Service\Object;

/**
 * 客户端数据对象
 * Class Client
 * @package Inphp\Service\Object
 */
class Client
{
    /**
     * 客户端ID
     * 可以使用 swoole request 对象的 fd
     * 也可以自定义
     * @var int
     */
    public $id = -1;

    /**
     * PHP_SESSION_ID
     * 若使用的是swoole服务，则需要另行实现
     * @var string
     */
    public $php_session_id = null;

    /**
     * 请求域名
     * @var string
     */
    public $host = '127.0.0.1';

    /**
     * 是不是用https访问
     * @var bool
     */
    public $https = false;

    /**
     * 来源域名
     * @var string
     */
    public $origin = '127.0.0.1';

    /**
     * 请求方式
     * @var string
     */
    public $method = "GET";

    /**
     * cookie
     * @var array
     */
    public $cookie = [];

    /**
     * session
     * @var array
     */
    public $session = [];

    /**
     * 地址参数
     * @var array
     */
    public $get = [];

    /**
     * post参数
     * @var array
     */
    public $post = [];

    /**
     * HTTP_RAW_POST_DATA
     * @var string
     */
    public $raw_post_data = '';

    /**
     * 文件上传
     * @var array
     */
    public $files = [];

    /**
     * 客户端IP
     * @var string
     */
    public $ip = '127.0.0.1';

    /**
     * 请求路径
     * @var string
     */
    public $uri = '';

    /**
     * 是否是AJAX请求
     * @var bool
     */
    public $ajax = false;

    /**
     * Client constructor.
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        $this->host     = $values['host'] ?? '127.0.0.1';
        $this->origin   = $values['origin'] ?? '127.0.0.1';
        $this->method   = $values['method'] ?? 'get';
        $this->method   = strtoupper($this->method);
        $this->ip       = $values['ip'] ?? '127.0.0.1';
        $this->get      = $values['get'] ?? [];
        $this->post     = $values['post'] ?? [];
        $this->files    = $values['files'] ?? [];
        $this->cookie   = $values['cookie'] ?? [];
        $this->session  = $values['session'] ?? [];
        $this->raw_post_data  = $values['raw_post_data'] ?? null;
        $this->uri      = $values['uri'] ?? '';
        $this->ajax     = $values['ajax'] ?? false;
        $this->https    = $values['https'] ?? false;
        $this->id       = $values['id'] ?? -1;
        $this->php_session_id = $values['php_session_id'] ?? null;
    }

    /**
     * 获取属性值
     * @param $name
     * @return mixed
     */
    public function get($name){
        return $this->{$name};
    }

    /**
     * 设置值
     * @param $name
     * @param $value
     */
    public function set($name, $value){
        $this->{$name} = $value;
    }
}