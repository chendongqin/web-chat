<?php

namespace app\api\controllers;

use app\base;
use lib\ku\session;
use lib\ku\token;
use lib\ku\tool;

class login extends base
{

    public function index()
    {
        $userName = $this->helper->getParam('user_name');
        $password = $this->helper->getParam('password');
        $db = $this->getDb();
        $user = $db->find('user', ['user_name' => $userName]);
        if (!$user) {
            return $this->error('用户不存在或密码错误');
        }
        if (!tool::valid($password, $user['password'])) {
            return $this->error('用户不存在或密码错误');
        }
//        $token = token::set('user_login', $user['id'], 600);
        $session = new session();
        $session::set('login_user', $user['id']);
        $user['login_at'] = date('YmdHis');
        $db->update('user',$user);
        return $this->success('成功', ['user_id' => $user['id']]);
    }


    /**
     * @return bool
     */
    public function register()
    {
        $userName = $this->helper->getParam('user_name');
        $name = $this->helper->getParam('name');
        $password = $this->helper->getParam('password');
        $sure = $this->helper->getParam('sure');
        if ($sure != $password) {
            return $this->error('两次密码不一致');
        }
        if (strlen($password) < 6 || strlen($password) > 20) {
            return $this->error('密码长度在6-20位');
        }
        $db = $this->getDb();
        $exist = $db->find('user', ['user_name' => $userName]);
        if ($exist) {
            return $this->error('用户名已占用');
        }
        $num = rand(1,3);
        $avatar = '/static/imgs/user/default_'.$num.'.jpg';
        $data = [
            'user_name' => $userName,
            'name'      => $name,
            'password'  => tool::encryption($password),
            'avatar'  => $avatar,
        ];
        $res = $db->insert('user', $data);
        if ($res === false) {
            return $this->error('注册失败');
        }
        return $this->success('成功');
    }


    public function out()
    {
        $session = new session();
        $session::del('login_user');
        return $this->success('成功');
    }
}