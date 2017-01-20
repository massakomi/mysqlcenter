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
$tables_rows = '<ul>';
foreach ($tables_array as $k => $v) {
	if (GET('table') == $v) {
		$tables_rows .= "<li><b style='font-size:16px; color: red'>$v</b>";
	} else {
		$tables_rows .= "<li><a href='" . $umaker->make('table', $v) . "'>$v</a>";
	}
}
$tables_rows .= '</ul>';
include(MS_DIR_TPL . 'import.htm.php');
?>