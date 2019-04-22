<?php
namespace app\index\controllers;
use app\base;
use lib\ku\session;

class login extends base
{

    public function index(){
        $userId = session::get('login_user');
        if($userId){
            $this->jump('/');
        }
    }

    public function register(){
       $userId = session::get('login_user');
       if($userId){
           $this->jump('/');
       }
    }
}