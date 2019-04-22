<?php
/**
 * Created by PhpStorm.
 * User: chendongqin
 * Date: 2019/4/17
 * Time: 11:36
 */
namespace lib\ku;

class session{

    public static function start(){
        session_start();
    }

    public static function set($key,$value){
        self::start();
        $_SESSION[$key] = $value;
        return true;
    }

    public static function del($key){
        self::start();
        unset($_SESSION[$key]);
        return true;
    }

    public static function get($key){
        self::start();
        if(isset($_SESSION[$key]))
            return $_SESSION[$key];
        return '';
    }
}