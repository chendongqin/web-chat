<?php
/**
 * Created by PhpStorm.
 * User: chendongqin
 * Date: 2019/4/12
 * Time: 12:27
 */

namespace server\ws;
use lib\db\sqli;
use lib\ku\redis;
class chat
{
    //消息类型
    const ERRORTYPE = 'error';
    const CLIENTYPE = 'client';
    const APPLYTYPE = 'apply';
    const CHATTYPE = 'chat';
    const CROWDTYPE = 'crowd';

    //设置消息推送常量类型
    const SELFMSG = 0;
    const TOONEMSG = 1;
    const TOFRIENDSMSG = 2;
    const CROWDMSG = 3;

    private $server = null;
    private $table = null;
    private $Db = null;
    private $redis = null;
    private $port = 8500;

    public function __construct($port = 8500)
    {
        $port = (int)$port;
        $this->port = empty($port) ? $this->port : $port;
        $this->init();
    }


    public function init()
    {
        //利用swoole_table创建表结构
        $this->table = new \swoole_table(1024);
        $this->table->column('id', \swoole_table::TYPE_INT, 11);
        $this->table->column('fd', \swoole_table::TYPE_INT, 4);
        $this->table->column('avatar', \swoole_table::TYPE_STRING, 1024);
        $this->table->column('feel', \swoole_table::TYPE_STRING, 1024);
        $this->table->column('nickname', \swoole_table::TYPE_STRING, 64);
        $this->table->create();
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
//        $server->on('open', [$this, 'open']);
//        $server->on('message', [$this, 'message']);
//        $server->on('close', [$this, 'close']);
//        $server->on('task', [$this, 'task']);
//        $server->on('finish', [$this, 'finish']);
//
//        $server->start();
    }


    public function open()
    {
        $this->server->on('open', function (\swoole_websocket_server $server, \swoole_http_request $request) {
            $res = $this->createUser($request->fd,$request->get['user_id']);
            $fds = $this->getFds(self::SELFMSG,$request->fd);
            if ($res) {
                $data = $this->buildJson(['msg'=>'登陆成功'],self::CLIENTYPE);
            } else {
                $data = $this->buildJson(['msg'=>'连接失败'],self::ERRORTYPE);
            }
            $this->intoTask($request->fd,$data, $fds);
        });
    }

    public function createUser($fd ,$user_id)
    {
        $db = $this->getDb();
        $user = $db->find('user', ['id' => $user_id]);
        if (empty($user)) {
            return false;
        }
        $this->setClient($user_id, $fd);
        $this->table->set($user_id, [
            'id'       => $user_id,
            'fd'       => $fd,
            'avatar'   => empty($user['avatar']) ? $user['avatar'] : '/static/imgs/user/default.jpg',
            'feel'     => $user['feel'],
            'nickname' => $user['name']
        ]);
        return true;
    }

    public function message()
    {
        $this->server->on('message', function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) {
            foreach ($server->connections as $key => $fd) {
                $user_message = $frame->data;
                $server->push($fd, $user_message);
            }

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
        $this->server->on('task',function ($server, $task_id, $from_id, $data){
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

    public function getFds($type, $fd = 0,$id = 0)
    {
        $fds = [];
        switch ($type) {
            case self::SELFMSG:
                $fds[] = $fd;
                break;
            case self::TOONEMSG:
                $fds[] = $fd;
                break;
            default:
                break;
        }
        return $fds;
    }

    public function close()
    {
        $this->server->on('close', function (\swoole_websocket_server $server, $fd) {
            $user_id = $this->getClient($fd);
            $this->table->del($user_id);
            $this->delClient($fd);
        });
    }

    public function start()
    {
        $this->server->start();
    }

    private function buildJson(array $data, $type, $code = 200, $status = true)
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

    public function setClient($user_id, $fd)
    {
        $key = 'chat_client_' . $fd;
        $redis = $this->getRedis();
        $redis->set($key, $user_id ,24*3600);
        return true;
    }

    public function getClient($fd)
    {
        $key = 'chat_client_' . $fd;
        $redis = $this->getRedis();
        return $redis->get($key);
    }

    public function delClient($fd)
    {
        $key = 'chat_client_' . $fd;
        $redis = $this->getRedis();
        return $redis->delete($key);
    }

}

