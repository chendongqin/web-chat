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
        $groups = $db->select('group', ['user_id' => $user['id']], 'id asc');
        array_unshift($groups, $this->group_first);
        $groups = array_merge($groups,$this->group_end);
        $this->assign('groups', $groups);
        return $this->display();
    }
}