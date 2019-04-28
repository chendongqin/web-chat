<?php

namespace server\ws;

use lib\db\sqli;
use lib\ku\redis;

class test
{
    private $user_table = null;
    private $server = null;
    private $Db = null;
    private $redis = null;
    private $port = 8700;


    public function __construct($port = 8700)
    {
        $port = (int)$port;
        $this->port = empty($port) ? $this->port : $port;
        $this->init();
    }


    public function init()
    {
        //利用swoole_table创建表结构
        $this->user_table = new \swoole_table(1024);
        $this->user_table->column('id', \swoole_table::TYPE_INT, 4);
        $this->user_table->column('client_id', \swoole_table::TYPE_INT, 11);
        $this->user_table->column('client_type', \swoole_table::TYPE_INT, 3);
        $this->user_table->create();
        //创建ws服务
        $this->server = new \swoole_websocket_server('0.0.0.0', $this->port);
        //服务设置
        $this->server->set([
            'task_worker_num' => 4
        ]);
        //创建连接并完成握手
        $this->open();
        $this->message();
        $this->close();
        $this->task();
        $this->finish();
        $this->start();
    }


    public function open()
    {
        $this->server->on('open', function (\swoole_websocket_server $server, \swoole_http_request $request) {
            $user_id = $request->get['user_id'];
            $type = $request->get['client_type'];
            $this->user_table->set($request->fd, [
                'id'          => $request->fd,
                'client_id'   => $user_id,
                'client_type' => $type,
            ]);
            $this->setClient($type, $user_id, $request->fd);
            $id = $this->timer_tick($type,$user_id);
            echo $id;
            $data = $this->buildJson(['msg' => '登陆成功:' . $user_id]);
            $this->intoTask($request->fd, $data, [$request->fd]);
        });
    }

    public function message()
    {
        $this->server->on('message', function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) {
            return true;
        });
    }

    public function getDb()
    {
        if (empty($this->Db)) {
            $this->Db = new sqli();
        }
        return $this->Db;
    }

    public function getRedis()
    {
        if (empty($this->redis)) {
            $this->redis = new redis();
        }
        return $this->redis;
    }

    public function push($fd, $data)
    {
        $this->server->push($fd, $data);
    }

    public function task()
    {
        $this->server->on('task', function ($server, $task_id, $from_id, $data) {
            $clients = $server->connections;
            if (count($data['to']) > 0) {
                $clients = $data['to'];
            }
            foreach ($clients as $fd) {
                $this->server->push($fd, $data['data']);
            }
        });
    }

    public function finish()
    {

    }

    public function timer_tick($type ,$user_id)
    {
        $db = $this->getDb();
        $timerId = \swoole_timer_tick(5000,function () use ($type , $user_id ,$db){
            $data = [];
            switch ($type){
                case 'chat':
                    $data = $db->select('chat_list',['user_id'=>$user_id,'is_read'=>0]);
                    break;
                default:

            }
            if($data){
                $msg = $this->buildJson($data,'new_order');
                $fd = $this->getClient($type,$user_id);
                $this->intoTask($fd,$msg,[$fd]);
            }
        });
        return $timerId;
    }

    public function close()
    {
        $this->server->on('close', function (\swoole_websocket_server $server, $fd) {
            $clientData = $this->user_table->get($fd);
            $this->delClient($clientData['client_type'], $clientData['user_id']);
            $this->user_table->del($fd);
        });
    }


    public function start()
    {
        $this->server->start();
    }


    private function buildJson(array $data, $type = 'client', $code = 200, $status = true)
    {
        return json_encode([
            'code'   => $code,
            'status' => $status,
            'type'   => $type,
            'data'   => $data
        ]);
    }

    public function intoTask($from_id, $data, $to_users)
    {
        $task = [
            'from' => $from_id,
            'to'   => $to_users,
            'data' => $data
        ];
        $this->server->task($task);
    }

    public function setClient($clientType, $user_id, $fd)
    {
        $key = 'e-gets_client_' . $clientType . '_' . $user_id;
        $redis = $this->getRedis();
        $redis->set($key, $fd, 24 * 3600);
        return true;
    }

    public function getClient($clientType, $user_id)
    {
        $key = 'e-gets_client_' . $clientType . '_' . $user_id;
        $redis = $this->getRedis();
        return $redis->get($key);
    }

    public function delClient($clientType, $user_id)
    {
        $key = 'e-gets_client_' . $clientType . '_' . $user_id;
        $redis = $this->getRedis();
        return $redis->delete($key);
    }

}