<?php
namespace Inphp\Service;

interface ISessionMiddleWare
{
    public function __construct(string $session_id);
    public function get(string $name, $default = null);
    public function set(string $name, $value);
    public function drop(string $name);
}