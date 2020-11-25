<?php
namespace Inphp\Service;

interface ISessionMiddleWare
{
    public function __construct(string $session_id);
    public function get(string $name = null, $default = null);
    public function set(string $name, $value = null);
    public function drop(string $name);
}