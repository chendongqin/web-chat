<?php
/**
 * Created by PhpStorm.
 * User: chendongqin
 * Date: 2019/4/12
 * Time: 12:27
 */
namespace server\ws;
class chat{

    private $server = null;
    public function __construct()
    {
        $server = new \swoole_websocket_server("0.0.0.0", 8500);
        $this->server = $server;
        $this->open();
        $this->message();
        $this->close();
    }

    private function open(){
        $this->server->on('open', function (\swoole_websocket_server $server, $request) {
            echo "server: handshake success with fd{$request->fd}\n";
        });
    }

    private function message(){
        $this->server->on('message', function (\swoole_websocket_server $server, $frame) {
            foreach($server->connections as $key => $fd) {
                $user_message = $frame->data;
                $server->push($fd, $user_message);
            }

        });
    }

    private function close(){
        $this->server->on('close', function ($ser, $fd) {
            echo "client {$fd} closed\n";
        });
    }

    public function start(){
        $this->server->start();
    }

}

