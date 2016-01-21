<?php
// namespace modules\websocket;
/*
 * 基于swoole扩展实现的websocket服务端
 * @author red
 *
 */
abstract class Swoole
{
    public $host;   // 监听地址
    public $port;   // 监听端口
    public $config; // swoole配置
    public $server; // swoole server
    public $connections = array();
    /**
     * 初始化swoole参数
     * @param unknown $host
     * @param unknown $port
     */
    function __construct($host,$port){
        $this->config = require_once 'config/swoole.php';
        $this->host = $host;
        $this->port = $port;
    }
    /**
     * 启动服务
     */
    function run() {
        $this->server = new \swoole_server($this->host, $this->port, $this->config['mode'],$this->config['sock_type']);
        $this->server->set($this->config['swoole_setting']);
        swoole_server_handler($this->server, 'onConnect', array($this, 'onConnect'));
        swoole_server_handler($this->server, 'onReceive', array($this, 'onReceive'));
        swoole_server_handler($this->server, 'onClose', array($this, 'onClose'));
        $this->server->start();
    }

    /**
     * 新连接进入时触发
     * @param unknown $serv
     * @param unknown $fd
     * @param unknown $from_id
     */
    function onConnect($serv,$fd,$from_id){}
    /**
     * 收到数据时触发
     * @param unknown $serv
     * @param unknown $fd
     * @param unknown $from_id
     * @param unknown $data
     */
    function onReceive($serv,$fd,$from_id,$data){
        // var_dump($data/*,$fd,$from_id,$data*/);
        if(!isset($this->connections[$fd])){
            $this->handShake($data, $fd);
            $this->connections[$fd] = time();
        }else {
            $data = $this->unmask($data);
            $this->onMessage($fd,$data);
        }
    }
    /**
     * 关闭一个连接时触发
     * @param unknown $serv
     * @param unknown $fd
     * @param unknown $from_id
     */
    function onClose($serv,$fd,$from_id){
        if(isset($this->connections[$fd])){
            unset($this->connections[$fd]);  // 清空连接
        }
    }
    /**
     * 接收到websocket数据时触发
     * @param unknown $fd
     * @param unknown $data
     */
    abstract function onMessage($fd,$data);
    /**
     * websocket 握手过程
     * @param string $receved 浏览器发过来的头信息
     * @param int $fd 连接的文件描述符
     */
    function handShake($receved,$fd){
        $headers = array();
        $lines = preg_split("/\r\n/", $receved);
        foreach($lines as $line)
        {
            $line = chop($line);
            if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
            {
                $headers[$matches[1]] = $matches[2];
            }
        }

        $secKey = $headers['Sec-WebSocket-Key'];
        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        //hand shaking header
        $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                "WebSocket-Origin: {$this->host}\r\n" .
                "WebSocket-Location: ws://{$this->host}:{$this->port}/demo/shout.php\r\n".
                "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
        $this->send($fd, $upgrade,FALSE);
    }
    /**
     * 解包发送的信息
     * @param unknown $text
     * @return Ambigous <string, boolean>
     */
    function unmask($text) {
        $length = ord($text[1]) & 127;
        if($length == 126) {
            $masks = substr($text, 4, 4);
            $data = substr($text, 8);
        }
        elseif($length == 127) {
            $masks = substr($text, 10, 4);
            $data = substr($text, 14);
        }
        else {
            $masks = substr($text, 2, 4);
            $data = substr($text, 6);
        }
        $text = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i%4];
        }
        return $text;
    }
    /**
     * 封包发送信息
     * @param unknown $text
     * @return string
     */
    function mask($text)
    {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);

        if($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif($length > 125 && $length < 65536)
        $header = pack('CCn', $b1, 126, $length);
        elseif($length >= 65536)
        $header = pack('CCNN', $b1, 127, $length);
        return $header.$text;
    }
    /**
     * 发送数据
     * @param int $fd   连接的文件描述符
     * @param string $data  发送的数据
     */
    function send($fd,$data,$mask=TRUE){
        if($mask){
            $data = $this->mask($data);
        }
        return swoole_server_send($this->server,$fd,$data);
    }
}
