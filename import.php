<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Импорт - не сделан!
 */
 
if (!defined('DIR_MYSQL')) { 
	exit('Hacking attempt');
}

$msc->pageTitle  = 'Импорт данных';

$pageProps = [
    'tables' => DatabaseManager::getTables(),
    'url' => $umaker->make('table', '%table%'),
    'table' => GET('table'),
];

if (isajax()) {
    return $pageProps;
}

$this->template($pageProps);
