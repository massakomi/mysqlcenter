<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Центральный скрипт для менеджера БД
 */

spl_autoload_register(function ($class) {
    include 'includes/' . $class . '.php';
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













