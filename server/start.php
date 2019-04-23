<?php

if(!isset($argv[1])){
    echo 'invalid request';
    return false;
}

define('DS', '/'); // 不支持
$class = __DIR__ .DS.str_replace('\\','/',$argv[1]).'.php';
if(!file_exists($class)){
    echo $class.' not exist';
    return false;
}
spl_autoload_register('loadClass');
//include $class;

$argv[1] = str_replace('/','\\',$argv[1]);
$className = '\server\\'.$argv[1];
if(isset($argv[2])){
    $app  = new $className($argv[2]);
}else{
    $app  = new $className();
}

function loadClass($class){
    $class = str_replace('\\', '/', $class);
    $class = ROOT_PATH . $class . '.php';
    var_dump($class);
    if (file_exists($class)) {
        include $class;
    } else {
        echo '没有找到文件';
        return false;
    }
}