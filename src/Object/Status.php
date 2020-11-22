<?php
namespace Small\Service\Object;

use Small\Service\Http\Service;

/**
 * 路由状态
 * Class Status
 * @package Small\Service\Object
 */
class Status
{
    public $status = 200;

    public $message = "ok";

    public $state = Service::CONTROLLER;

    public $controller = null;

    public $method = "index";

    public $view = null;

    public $path = '';

    public function __construct(array $values)
    {
        $this->status       = $values['status'] ?? 200;
        $this->message      = $values['message'] ?? 'ok';
        $this->state        = $values['state'] ?? Service::CONTROLLER;
        $this->controller   = $values['controller'] ?? null;
        $this->method       = $values['method'] ?? 'index';
        $this->view         = $values['view'] ?? null;
        $this->path         = $values['path'] ?? null;
    }
}