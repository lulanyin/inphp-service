<?php
namespace Inphp\Service;

interface IRequest
{
    public function request(string $name, $default = null);

    public function get(string $name, $default = null);

    public function post(string $name, $default = null);
}