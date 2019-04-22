<?php
/**
 * Created by PhpStorm.
 * User: chendongqin
 * Date: 2019/4/22
 * Time: 13:33
 */

namespace app\api\controllers;

use app\userApi;

class  user extends userApi
{

    public function index()
    {


    }


    //添加好友
    public function addfrieds()
    {
        $user = $this->getLoginUser();
        $friendId = $this->helper->getParam('friend_id', 0, 'int');
        if($user['id'] == $friendId){
            return $this->error('不能加自己为好友');
        }
        $groupId = $this->helper->getParam('group_id', 0, 'int');
        $db = $this->getDb();
        $friend = $db->find('user', ['id' => $friendId]);
        if (empty($friend)) {
            return $this->error('没有改用户');
        }
        $userFriend = $db->find('friend', ['user_id' => $user['id'], 'friend_id' => $friendId], 'id desc');
        if (!empty($userFriend)) {
            return $this->error($friend['name'] . ' 已经是您的好友');
        }
        $exist = $db->find('apply', ['user_id' => $user['id'], 'friend_id' => $friendId], 'id desc');
        if (!empty($exist)) {
            $exist['status'] = 0;
            $exist['group_id'] = $groupId;
            $exist['is_read'] = 0;
            $exist['friend_is_read'] = 0;
            $res = $db->update('apply', $exist);
        } else {
            $data = [
                'user_id'   => $user['id'],
                'friend_id' => $friendId,
                'group_id'  => $groupId,
            ];
            $res = $db->insert('apply', $data);
        }
        if (!$res) {
            return $this->error();
        }
        return $this->success();
    }

    public function search()
    {
        $searchStr = $this->helper->getParam('searchStr','','string');
        $page = $this->helper->getParam('page',1,'int');
        $pageLimit = $this->helper->getParam('pagelimit',1,'int');
        if(empty($searchStr)){
            return $this->error('查询条件不能为空');
        }
        if(is_numeric($searchStr)){
            $where = "`id` like '".$searchStr."%' OR `user_name`  like '".$searchStr."%' OR `name` like '%".$searchStr."%'";
        }else{
            $where = "`user_name` like '".$searchStr."%' OR `name` like '%".$searchStr."%'";
        }
        $db = $this->getDb();
        $count = 0;
        $users = $db->select('user',$where,'`id` DESC',$page,$pageLimit,$count,['id','user_name','name']);
        $data = [
            'total'=>$count,'pages'=>$this->getPages($count,$pageLimit),'users'=>$users,'page'=>$page
        ];
        return $this->success('成功',$data);
    }

    public function chat()
    {

    }


}