<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

include_once(DIR_MYSQL . 'includes/Export.class.php');

/**
 * Сравнение баз данных
 */

if (!defined('DIR_MYSQL')) { 
    exit('Hacking attempt');
}
$msc->pageTitle = 'Сравнение баз данных';

// Проверка
$databases = explode(';', POST('dbs'));
if (!$databases) {
    $databases = POST('databases');
}
if ($databases && count($databases) < 2) {
    $msc->addMessage('Вы не выбрали базы данных для сравнения');
    return null;
}

function pageProps($databases) {
    global $msc;

    // Создание начальных массивов
    $dbArray = array();
    foreach ($databases as $k => $v) {
        $result = $msc->query('SHOW TABLE STATUS FROM '.$v);
        while ($row = mysqli_fetch_object($result)) {
            $dbArray[$v][$row->Name]= $row;
        }
    }

    $exportArray = [];
    $export = new MySQLExport();
    $export->setComments(0);
    $export->setOptionsStruct(0, $addAuto=0, 0);
    foreach ($dbArray as $db => $tables) {
        foreach ($tables as $table => $values) {
            $export->data = null;
            $export->setDatabase($db);
            $export->setTable($table);
            $exportData = $export->exportStructure(0,0);
            $exportData = str_replace(' PACK_KEYS=0', '', $exportData);
            $exportData = preg_replace('~COMMENT=".*"~U', '', $exportData);
            $exportArray [$db][$table] = $exportData;
        }
    }
    return compact('databases', 'dbArray', 'exportArray');
}


$pageProps = pageProps($databases);
//echo '<pre>'; print_r($pageProps); echo '</pre>'; exit;
if (isajax()) {
    return $pageProps;
}
include(MS_DIR_TPL . 'db_compare.htm.php');