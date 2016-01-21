<?php
header("Content-type: text/html; charset=utf-8");
/**
 * 自动加载类
 * @param unknown $className
 */
// var_dump($className);echo '1111';
spl_autoload_register(function($className){
    $className=str_replace('\\', '/', $className);
    require "./{$className}.php";
});
/*
 * swoole websocket服务
 */
class server extends Swoole{
    /**
     * 监听到消息时执行
     * @see \modules\websocket\Swoole::onMessage()
     */
    function onMessage($clinet_id, $data){
        $received = json_decode($data); //json decode
        $response = array('type'=>'usermsg', 'name'=>$received->name, 'message'=>$received->message, 'color'=>$received->color);
        $this->send($clinet_id, json_encode($response)); //send data
    }

}
$server = new server('0.0.0.0', '4401');
$server->run();
