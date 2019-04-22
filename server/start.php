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
include $class;
$argv[1] = str_replace('/','\\',$argv[1]);
$className = '\server\\'.$argv[1];
$app  = new $className();
$app->start();
unset($app);
