<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

/**
 *	Центральный скрипт для менеджера БД
 */

if (file_exists('Debugger.php')) {
    define('DEBUGGER_ONLY', 1);
    include_once 'Debugger.php';
    global $debugger;
    $debugger = new Debugger();
}

define('DIR_MYSQL', './');
require_once DIR_MYSQL . 'config.php';

$actPro = new ActionProcessor;
$umaker = new UrlMaker();

require_once DIR_MYSQL . 'includes/PageLayout.php';
$pagel = new PageLayout;
$pagel->display();
