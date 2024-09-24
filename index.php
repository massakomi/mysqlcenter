<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Центральный скрипт для менеджера БД
 */

$str = "\n".date('Y-m-d H:i:s')." ".$_SERVER['REMOTE_ADDR'].' '.$_SERVER['REQUEST_URI'].' ['.$_SERVER['REQUEST_METHOD'].']';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $str .= (count($_POST) ? print_r($_POST, 1) : ' empty $_POST');
}
fwrite(fopen(dirname(__FILE__).'/'.basename(__FILE__, '.php').'.txt', 'a+'), $str);

spl_autoload_register(function ($class) {
    include_once 'includes/' . $class . '.php';
});

if (file_exists('Debugger.php')) {
    define('DEBUGGER_ONLY', 1);
    include_once 'Debugger.php';
    global $debugger;
    $debugger = new Debugger();
}

define('DIR_MYSQL', './');

$pagel = new PageLayout;
require_once DIR_MYSQL . 'config.php';

$actPro = new ActionProcessor;
$umaker = new UrlMaker();

$pagel->display();













