<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

if (!defined('DIR_MYSQL')) {
    define('DIR_MYSQL', dirname(__FILE__).'/');
}
require_once DIR_MYSQL . 'config.php';

$actPro = new ActionProcessor(true);
