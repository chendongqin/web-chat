<?php

/**
 * Created by PhpStorm.
 * User: chendongqin
 * Date: 2019/4/14
 * Time: 11:27
 */
namespace app;
class start
{

    private $conf = [];
    static $MODULE = '';
    static $CONTROLLER = '';
    static $ACTION = '';

    public function __construct()
    {
        $this->conf = include ROOT_PATH . '/conf/config.php';
        self::$MODULE = $this->conf['default_module'];
        self::$CONTROLLER = $this->conf['default_controller'];
        self::$ACTION = $this->conf['default_action'];
    }

    function run()
    {
        spl_autoload_register(array($this, 'loadClass'));
        $this->route();
    }

    public function route()
    {
        $uri = $_SERVER['REQUEST_URI'];
        // 清除?之后的内容
        $position = strpos($uri, '?');
        $uri = $position === false ? $uri : substr($uri, 0, $position);
        $uri = trim($uri, '/');
        if ($uri) {
            $data = explode('/', $uri);
            $count = count($data);
            switch ($count) {
                case 1:
                    self::$MODULE = $data[0];
                    break;
                case 2:
                    self::$MODULE = $data[0];
                    self::$CONTROLLER = $data[1];
                    break;
                case 3:
                    self::$MODULE = $data[0];
                    self::$CONTROLLER = $data[1];
                    self::$ACTION = $data[2];
                    break;
                default:
                    break;
            }
        }
        $controller = '\app\\' . self::$MODULE . '\controllers\\' . self::$CONTROLLER;
        if (!class_exists($controller)) {
            throw new \Error($controller . '控制器不存在',500);
        }
        if (!method_exists($controller, self::$ACTION)) {
            throw new \Error(self::$ACTION . '方法不存在',500);
        }
        $dispatch = new $controller($controller, self::$ACTION);
        $dispatch->_setInit(self::$MODULE,self::$CONTROLLER,self::$ACTION);
        $action = self::$ACTION;
        $dispatch->$action();
    }

    public static function loadClass($class)
    {
        $class = str_replace('\\', '/', $class);
        $class = ROOT_PATH . $class . '.php';
        if (file_exists($class)) {
            include $class;
        } else {
            throw new \Error('没有找到文件', 400);
        }
    }


}