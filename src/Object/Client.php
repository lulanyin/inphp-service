<?php
namespace Inphp\Service\Object;

/**
 * 客户端数据对象
 * Class Client
 * @package Inphp\Service\Object
 */
class Client
{
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
        $this->ajax     = $values['ajax'] == true;
        $this->https    = $values['https'] == true;
    }
}