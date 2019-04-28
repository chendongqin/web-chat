<?php

namespace app\index\controllers;
//use app\base;
use app\userBase;

class index extends userBase
//class index extends base
{

    private $group_first = [
        'id'=>0,'name'=>'我的好友'
    ];

    private $group_end = [
        ['id'=>1,'name'=>'陌生人'],
        ['id'=>2,'name'=>'黑名单']
    ];

    private $set = [
        ['id'=>3,'name'=>'修改信息'],
        ['id'=>4,'name'=>'添加好友'],
    ];

    public function index()
    {
        $user = $this->getLoginUser();
        $db = $this->getDb();
        //好友分组
        $groups = $db->select('group', ['user_id' => $user['id']], 'id asc');
        array_unshift($groups, $this->group_first);
        $groups = array_merge($groups,$this->group_end);
        //验证通知
        $where = ['receive_user_id'=>$user['id'],'is_read'=>0];
        $applyNum = $db->count('apply',$where);
        //好友分组
        $where = ['user_id'=>$user['id']];
        $friends = [];
        $text_nums = [];
        foreach ($groups as $group){
            $where['group_id']= $group['id'];
            $where['on_line']= 1;
            $joins = [
                'friends'=>['as'=>'f'],
                'user'=>['as'=>'u','on'=>'f.friend_user_id = u.id','join'=>'left'],
            ];
            $on_lines = $db->join($joins,$where,'u.name asc');
            $where['on_line']= 0;
            $off_lines = $db->join($joins,$where,'u.name asc');
            $friends[$group['id']]['on_lines'] = $on_lines;
            $on_line_num = count($on_lines);
            $friends[$group['id']]['on_lines_num'] = $on_line_num;
            $friends[$group['id']]['total_num'] = $on_line_num + count($off_lines);
            $friends[$group['id']]['off_lines'] = $off_lines;
            $text_num = $db->count('chat_list',['user_id'=>$user['id'],'group_id'=>$group['id'],'is_read'=>0,'is_receive'=>1]);
            $text_nums[$group['id']] = $text_num;
        }
        $this->assign('groups', $groups);
        $this->assign('friends', $friends);
        $this->assign('text_nums', $text_nums);
        $this->assign('use_set',$this->set);
        $this->assign('applyNum',$applyNum);
        return $this->display();
    }

    public function test(){
        return $this->display();
    }
}