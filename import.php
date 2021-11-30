<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

/**
 * Импорт - не сделан!
 */
 
if (!defined('DIR_MYSQL')) { 
	exit('Hacking attempt');
}

classLoad('DatabaseManager');
$msc->pageTitle  = 'Импорт данных';

$tables_array = DatabaseManager::getTables();	

include(MS_DIR_TPL . 'import.htm.php');
