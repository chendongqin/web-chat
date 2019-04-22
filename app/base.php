<?php
/**
 * Created by PhpStorm.
 * User: chendongqin
 * Date: 2019/4/14
 * Time: 12:51
 */

namespace app;

use lib;

class base
{
    protected $db = null;
    protected $redis = null;
    protected $helper = null;
    protected $module = '';
    protected $controller = '';
    protected $action = '';
    protected $viewShow = true;
    protected $viewData = [];

    public function __construct()
    {
        $this->helper = new lib\helper();
        $this->_init();
    }

    protected function _init()
    {

    }

    protected function getRedis()
    {
        if (empty($this->redis)) {
            $this->redis = new lib\ku\redis();
        }
        return $this->redis;
    }

    protected function getDb()
    {
        if (empty($this->db)) {
            $this->db = new lib\db\sqli();
        }
        return $this->db;
    }

    protected function json(array $data, $callback = null)
    {
        $this->disableView();
        $callback = (!!$callback && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', (string)$callback)) ? $callback : null;
        if ($callback === null) {
            header('Content-type: application/json; charset=utf-8');
            echo \json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            header('Content-type: application/javascript; charset=utf-8');
            echo $callback, '(', \json_encode($data), ');';
        }
        return false;
    }

    protected function success($msg = '成功', $data = null, $callback = null)
    {
        $returnData = [
            'msg'    => $msg,
            'status' => true,
            'code'   => 200,
            'data'   => $data
        ];
        return $this->json($returnData, $callback);
    }

    protected function error($msg = '失败', $code = 500, $data = null, $callback = null)
    {
        $returnData = [
            'msg'    => $msg,
            'status' => false,
            'code'   => $code,
            'data'   => $data
        ];
        return $this->json($returnData, $callback);
    }

    protected function getPages($count, $pageLimit)
    {
        if ($pageLimit <= 0) {
            return 0;
        }
        return ceil($count / $pageLimit);
    }

    /**
     * @return string
     */
    protected function getModule()
    {
        return $this->module;
    }

    /**
     * @return string
     */
    protected function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    protected function getAction()
    {
        return $this->action;
    }

    protected function disableView()
    {
        $this->viewShow = false;
        return $this;
    }

    protected function jump($url)
    {
        Header("Location:$url");
    }

    public function _setInit($module, $controller, $action)
    {
        $this->module = $module;
        $this->controller = $controller;
        $this->action = $action;
        return $this;
    }

    protected function assign($key ,$value){
        $key = (string)$key;
        $this->viewData[$key] = $value;
        return $this;
    }

    protected function assignAll(array $data){
        $this->viewData = array_merge($data,$this->viewData);
        return $this;
    }

    protected function display($html = '', $data = [])
    {
        if (!$this->viewShow) {
            return false;
        }
        $data = array_merge($data,$this->viewData);
        extract($data);
        unset($data);
        if(empty($html)){
            $conf = include ROOT_PATH . '/conf/config.php';
            $html = $this->getAction() . '.' . $conf['html_template'];
        }
        $file = APP_PATH . $this->getModule() . '/views/' . $this->getController() . '/' .$html;
        if (!file_exists($file)) {
            throw new \Error(' Do not find file:' . $file);
        }
        include $file;
    }

    public function __destruct()
    {

    }

//    public function getParam($key, $default = '', $filter = 'trim')
//    {
//        return call_user_func(array('lib\helper', 'getParam'), $key, $default, $filter);
//    }
//
//    public function __call($name, $arguments)
//    {
//        $helper = new lib\helper();
//        if (!method_exists($helper, $name)) {
//            throw new \Error($name . ':NOT EXIST!');
//        }
//        return call_user_func_array(array($helper, $name), $arguments);
//    }
}