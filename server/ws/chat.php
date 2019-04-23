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
        $this->table->column('user_id', \swoole_table::TYPE_INT, 4);
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
        $this->table->set($fd, [
            'id'       => $fd,
            'user_id'  => $user_id,
            'avatar'   => empty($user['avatar']) ? $user['avatar'] : '/static/imgs/user/default.jpg',
            'feel'     => $user['feel'],
            'nickname' => $user['name']
        ]);
        $user['on_line'] = 1;
        $db->update('user',$user);
        return true;
    }

    public function message()
    {
        $this->server->on('message', function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) {
            $receive = json_decode($frame->data,true);
            if(isset($receive['quest_type'])){
                $quest_type = $receive['quest_type'];
                unset($receive['quest_type']);
                $this->$quest_type($frame->fd,$receive);
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
            $user_id = $this->table->get($fd,'user_id');
            $this->table->del($fd);
            $this->delClient($user_id);
            $db = $this->getDb();
            $db->update('user',['id'=>$user_id,'on_line'=>0]);
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
        $key = 'chat_client_' . $user_id;
        $redis = $this->getRedis();
        $redis->set($key, $fd ,24*3600);
        return true;
    }

    public function getClient($user_id)
    {
        $key = 'chat_client_' . $user_id;
        $redis = $this->getRedis();
        return $redis->get($key);
    }

    public function delClient($user_id)
    {
        $key = 'chat_client_' . $user_id;
        $redis = $this->getRedis();
        return $redis->delete($key);
    }

    public function errorMsg($fd,$msg = '错误'){
        $data = $this->buildJson(['msg'=>$msg],self::ERRORTYPE);
        $this->intoTask($fd,$data,[$fd]);
        return false;
    }


    //消息推送处理
    //添加好友
    public function add_apply($fd,$receive){
        $user_id = $this->table->get($fd,'user_id');
        if($user_id == $receive['friend_id']){
            return $this->errorMsg($fd,'不能添加自己为好友');
        }
        $db = $this->getDb();
        $friend = $db->find('user', ['id' => $receive['friend_id']]);
        if (empty($friend)) {
            return $this->errorMsg($fd,'没有该用户信息');
        }
        $userFriend = $db->find('friends', ['user_id' => $user_id, 'friend_id' => $receive['friend_id']], 'id desc');
        if (!empty($userFriend)) {
            return $this->errorMsg($fd,$friend['name'] . ' 已经是您的好友');
        }
        $exist = $db->find('apply', ['user_id' => $user_id, 'friend_id' => $receive['friend_id']], 'id desc');
        if (!empty($exist)) {
            $exist['status'] = 0;
            $exist['reason'] = $receive['reason'];
            $exist['group_id'] = $receive['group_id'];
            $exist['is_read'] = 0;
            $exist['friend_is_read'] = 0;
            $exist['update_at'] = date('YmdHis');
            $res = $db->update('apply', $exist);
        } else {
            $data = [
                'user_id'   => $user_id,
                'friend_id' => $receive['friend_id'],
                'group_id'  => $receive['group_id'],
                'reason' => $receive['reason'],
                'create_at' => date('YmdHis'),
                'update_at' => date('YmdHis'),
            ];
            $res = $db->insert('apply', $data);
        }
        if($res === false){
            return $this->errorMsg($fd,'添加好友申请失败');
        }
        $friend_fd = $this->getClient($receive['friend_id']);
        if(!empty($friend_fd)){
            //验证通知
            $applyRequest = $db->count('apply',['friend_id'=>$receive['friend_id'],'friend_is_read'=>0]);
            $applyResponse = $db->count('apply',['user_id'=>$receive['friend_id'],'is_read'=>0]);
            $applyNum = $applyRequest + $applyResponse;
            $msg = $this->buildJson(['apply_num'=>$applyNum],self::APPLYTYPE);
            $this->intoTask($fd,$msg,[$friend_fd]);
        }

        return true;
    }


}

