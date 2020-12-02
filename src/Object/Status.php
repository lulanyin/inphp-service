<?php
namespace Inphp\Service\Object;

/**
 * 路由状态
 * Class Status
 * @package Inphp\Service\Object
 */
class Status
{
    /**
     * 响应状态，目前只处理 200，404
     * @var int|mixed
     */
    public $status = 200;

    /**
     * 消息，在发生错误的时候，可以加上
     * @var mixed|string
     */
    public $message = "ok";

    /**
     * 自定义数据...按需处理
     * @var mixed|string
     */
    public $state = 'controller';

    /**
     * 控制器类名
     * @var mixed|null
     */
    public $controller = null;

    /**
     * 控制器需要执行的方法名
     * @var mixed|string
     */
    public $method = "index";

    /**
     * 视图文件夹
     * @var mixed|null
     */
    public $view_dir = null;

    /**
     * 视图文件名，并非全路径，相对版块路径
     * @var mixed|null
     */
    public $view = null;

    /**
     * 进入的版块路径
     * @var mixed|string|null
     */
    public $path = '';

    /**
     * 请求的 uri
     * @var mixed|string|null
     */
    public $uri = '';

    /**
     * 内容响应的格式
     * @var string
     */
    public $response_content_type = 'default';

    /**
     * Status constructor.
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->status       = $values['status'] ?? 200;
        $this->message      = $values['message'] ?? 'ok';
        $this->state        = $values['state'] ?? 'controller';
        $this->controller   = $values['controller'] ?? null;
        $this->method       = $values['method'] ?? 'index';
        $this->view_dir     = $values["view_dir"] ?? null;
        $this->view         = $values['view'] ?? null;
        $this->path         = $values['path'] ?? null;
        $this->uri          = $values['uri'] ?? null;
        $this->response_content_type = $values['response_content_type'] ?? 'default';
    }
}