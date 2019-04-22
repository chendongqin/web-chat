<?php

namespace app\index\controllers;
//use app\base;
use app\userBase;

class index extends userBase
//class index extends base
{

    public function index()
    {
        $user = $this->getLoginUser();
        $db = $this->getDb();
        $first = $db->find('group', ['user_id' => 0], 'id asc');
        $groups = $db->select('group', ['user_id' => $user['id']], 'id asc');
        array_unshift($groups, $first);
        $this->assign('groups', $groups);
        return $this->display();
    }

    public function addfrieds(){
//        $user
        $user = $this->getLoginUser();
    }

    public function chat()
    {

    }
}