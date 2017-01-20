<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

if (!defined('DIR_MYSQL')) {
    define('DIR_MYSQL', dirname(__FILE__).'/');
}
require_once DIR_MYSQL . 'config.php';

$actPro = new ActionProcessor(true);

echo '$("msAjaxQueryDiv").innerHTML = $("msAjaxQueryDiv").innerHTML + "'.str_replace('"', '\'', $msc->getMessages()).'"; ';


?>