<?php
/**
 * Created by PhpStorm.
 * User: chendongqin
 * Date: 2019/4/13
 * Time: 17:06
 */

define('APP_PATH', __DIR__ . '/../app/');
define('ROOT_PATH', __DIR__ . '/../');
define('PUBLIC_PATH', __DIR__ . '/');

include APP_PATH.'/start.php';
$start = new app\start();

$start->run();
