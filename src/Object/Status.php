<?php
namespace Inphp\Service\Object;

/**
 * 路由状态
 * Class Status
 * @package Inphp\Service\Object
 */
class Status
{
    public $status = 200;

    public $message = "ok";

    public $state = 'controller';

    public $controller = null;

    public $method = "index";

    public $view = null;

    public $path = '';

    public $uri = '';

    public function __construct(array $values)
    {
        $this->status       = $values['status'] ?? 200;
        $this->message      = $values['message'] ?? 'ok';
        $this->state        = $values['state'] ?? 'controller';
        $this->controller   = $values['controller'] ?? null;
        $this->method       = $values['method'] ?? 'index';
        $this->view         = $values['view'] ?? null;
        $this->path         = $values['path'] ?? null;
        $this->uri          = $values['uri'] ?? null;
    }
}