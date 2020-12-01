<?php
/**
 * Create By Hunter
 * 2020/12/1 3:11 下午
 *
 */
namespace Inphp\Service\Object;

/**
 * 消息通讯的数据对象
 * Class Message
 * @package Inphp\Service\Object
 */
class Message
{
    /**
     * 事件，用于 Ws
     * @var mixed|null
     */
    public $event = null;

    /**
     * 错误码
     * @var int|mixed
     */
    public $error = 0;

    /**
     * 消息
     * @var mixed|null
     */
    public $message = null;

    /**
     * 数据
     * @var mixed|null
     */
    public $data = null;

    /**
     * 初始化
     * Message constructor.
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        $this->event = $values['event'] ?? null;
        $this->error = $values['error'] ?? 0;
        $this->message = $values['message'] ?? null;
        $this->data = $values['data'] ?? null;
    }

    /**
     * @param null $value
     * @return Message
     */
    public function event($value = null){
        $this->event = $value;
        return $this;
    }

    /**
     * @param null $value
     * @return Message
     */
    public function error($value = null){
        $this->error = $value;
        return $this;
    }

    /**
     * @param null $value
     * @return Message
     */
    public function message($value = null){
        $this->message = $value;
        return $this;
    }

    /**
     * @param null $value
     * @return Message
     */
    public function data($value = null){
        $this->data = $value;
        return $this;
    }

    /**
     * 生成JSON
     * @return string
     */
    public function toJson(){
        $json = [
            'error'     => $this->error
        ];
        //不为空才加入
        if(!is_null($this->event)){
            $json['event']  = $this->event;
        }

        if(!is_null($this->message)){
            $json['message'] = $this->message;
        }

        if(!is_null($this->data)){
            $json['data']   = $this->data;
        }

        return json_encode($json, JSON_UNESCAPED_UNICODE);
    }
}