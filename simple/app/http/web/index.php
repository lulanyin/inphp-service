<?php
namespace Inphp\ServiceSimple\app\http\web;

use Inphp\Service\Http\Session;

class index
{
    public function index(){
        //Session::set("verify_code", "hijk");
        echo Session::get("verify_code", 'not set');
    }
}