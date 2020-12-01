<?php
namespace Inphp\ServiceSimple\app\ws\web;

use Inphp\Service\Object\Message;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class index
{
    public function index(){
        echo 'home index'.PHP_EOL;
    }

    public function detail(){

        echo 'detail'.PHP_EOL;
    }

    /**
     * 聊天测试
     * @param Server $server
     * @param Frame $frame
     * @param Message $json
     */
    public function chat(Server $server, Frame $frame, Message $json){
        echo '收到了....'.PHP_EOL;
        $time = rand(1000, 5000);
        $server->after($time, function () use($server, $frame, $json, $time){
            echo "发送回执...".PHP_EOL;
            $server->push($frame->fd, "您对我说了：{$json->data}，我已经收到了，但我延迟了{$time}毫秒才回复你！");
        });
    }
}