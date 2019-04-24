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

    //查询返回结果
    public function search()
    {
        $searchStr = $this->helper->getParam('searchStr', '', 'string');
        $page = $this->helper->getParam('page', 1, 'int');
        $pageLimit = $this->helper->getParam('pagelimit', 4, 'int');
        if (empty($searchStr)) {
            return $this->error('查询条件不能为空');
        }
        if (is_numeric($searchStr)) {
            $where = "`id` like '" . $searchStr . "%' OR `user_name`  like '" . $searchStr . "%' OR `name` like '%" . $searchStr . "%'";
        } else {
            $where = "`user_name` like '" . $searchStr . "%' OR `name` like '%" . $searchStr . "%'";
        }
        $db = $this->getDb();
        $count = 0;
        $users = $db->select('user', $where, '`id` DESC', $page, $pageLimit, $count, ['id', 'user_name', 'name']);
        $data = [
            'total' => $count, 'pages' => $this->getPages($count, $pageLimit), 'users' => $users, 'page' => $page
        ];
        return $this->success('成功', $data);
    }

    //获取请求的消息通知
    public function applylist()
    {
        $limit = $this->helper->getParam('limit', 3, 'int');
        $user = $this->getLoginUser();
        $db = $this->getDb();
        $where = ['receive_user_id' => $user['id']];
        $count = 0;
        $result = $db->select('apply', $where, 'is_read asc,update_at desc', 1, $limit, $count);
        $lists = [];
        foreach ($result as $item) {
            $item['reason'] = empty($item['reason']) ? '' : '：' . $item['reason'];
            $applyUser = $db->find('user', ['id' => $item['user_id']]);
            if ($item['reply'] > 0) {
                $status = $item['status'] == 1 ? '通过了' : '拒绝了';
                $str = $status . '您的好友申请' . $item['reason'];
            } else {
                $applyUser = $db->find('user', ['id' => $item['user_id']]);
                $str = '请求添加好友' . $item['reason'];
            }
            $list['user_name'] = $applyUser['name'];
            $list['user_avatar'] = $applyUser['avatar'];
            $list['update_at'] = date('Y-m-d H:i:s', strtotime($item['update_at']));
            $list['msg'] = $str;
            $list['id'] = $item['id'];
            $list['is_reply'] = $item['is_reply'];
            $lists[] = $list;
        }
        return $this->success('成功', ['lists' => $lists, 'total' => $count]);
    }


    public function look_apply()
    {
        $id = $this->helper->getParam('id', 0, 'int');
        $user = $this->getLoginUser();
        $db = $this->getDb();
        $apply = $db->find('apply', ['receive_user_id' => $user['id'], 'id' => $id]);
        if (!$apply) {
            return $this->error('没有该消息');
        }
        $data = [];
        if($apply['is_reply']>0){
            $applyUser = $db->find('user', ['id' => $apply['user_id']]);
            $status = $apply['status'] == 1 ? '通过了' : '拒绝了';
            $reason = empty($item['reason']) ? '' : '：' . $item['reason'];
            $data['msg'] = $applyUser['name'] . ' ' . $status . '您的好友申请' . $reason;
        }
        if($apply['is_read'] == 0){
            $apply['is_read'] = 1;
//            $apply['update_at'] = date('YmdHis');
            $db->update('apply',$apply);
        }
        return $this->success('成功',$data);
    }


    public function get_friends()
    {
        $user = $this->getLoginUser();
        $group_id = $this->helper->getParam('group_id', 0, 'int');
        $where = ['user_id' => $user['id'], 'group_id' => $group_id, 'on_line' => 1];
        $db = $this->getDb();
        $on_lines = $db->select('friends', $where, ['name' => 'asc']);
        $on_lines_num = count($on_lines);
        $where['on_line'] = 0;
        $off_lines = $db->select('friends', $where, ['name' => 'asc']);
        $off_lines_num = count($off_lines);

    }


    public function chat()
    {

    }


}