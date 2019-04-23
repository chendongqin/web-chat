<?php
/**
 * Created by PhpStorm.
 * User: chendongqin
 * Date: 2019/4/15
 * Time: 17:04
 */
namespace lib\ku;
class redis{

    private $redis = null;
    public function __construct($conf = [])
    {
        $config = include ROOT_PATH . '/conf/redisconfig.php';
        $conf = array_merge($config,$conf);
        $redis = new \Redis();
        try{
            $redis->pconnect($conf['host'],$conf['port'],0.0,$conf['index']);
            $this->redis = $redis;
        }catch (\Error $error){
            die('连接错误 host:'.$conf['host'].',port:'.$conf['port'].',index:'.$conf['index']);
        }
    }

    public function keys($pattern){
        return $this->redis->keys($pattern);
    }

    public function set($key,$value,$timeOut=0){
        if ($timeOut){
            $this->redis->set($key,$value,$timeOut);
        }else{
            $this->redis->set($key,$value);
        }
        return $this;
    }

    public function get($key){
        return $this->redis->get((string)$key);
    }

    public function delete($key){
        return $this->redis->del($key);
    }

    public function hKeys( $key ) {
        $this->redis->hKeys($key);
    }

    public function hSet($key,$hKey,$value){
        $this->redis->hSet($key,$hKey,$value);
        return $this;
    }

    public function hGet($key,$hKey){
        return $this->redis->hGet($key,$hKey);
    }

    public function hDel($key,$hKey){
        return $this->redis->hDel($key,$hKey);
    }

    public function close(){
        $this->redis->close();
    }

    public function __destruct()
    {
       $this->close();
    }


}