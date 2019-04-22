<?php
/**
 * Created by PhpStorm.
 * User: chendongqin
 * Date: 2019/4/15
 * Time: 22:20
 */

namespace lib\ku;

class token
{

    private static $serece = 'af61c6cbd9dfef0b6c29d1db907ed543';

    public static function set($key, $value, $timeOut = 0)
    {
        $redis = new redis();
        if ($yes = $redis->get($key . $value)) {
            $redis->delete($yes);
        }
        $token = self::create($value);
        $redis->set($key . $value, $token, $timeOut);
        $redis->set($token, $value, $timeOut);
        return $token;
    }

    public static function create($value)
    {
        $num = $value . self::$serece;
        return sha1($num);
    }
}