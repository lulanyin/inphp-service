<?php
namespace Inphp\ServiceSimple\app\http\api;

class index
{
    public function index(){
        return [
            'rows'  => 5,
            'page'  => 1,
            'pages' => 1,
            'list'  => []
        ];
    }
}