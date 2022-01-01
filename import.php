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

$pageProps = [
    'tables' => DatabaseManager::getTables(),
    'url' => $umaker->make('table', '%table%'),
    'table' => GET('table'),
];

if (isajax()) {
    return $pageProps;
}

include(MS_DIR_TPL . 'import.htm.php');
