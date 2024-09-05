<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

if (!defined('DIR_MYSQL')) { 
	exit('Hacking attempt');
}
if ($msc->table == '') {
	exit('Не указана таблица в запросе');
}

$tableData = [];

/**
 * Вставка/изменение рядов
 */
if (GET('row') == '' && POST('row') == '') {
    $msc->pageTitle = 'Добавить строки в таблицу';
    $fields = getFields($msc->table);
    $isAdd = true;
} else {

    $msc->pageTitle = 'Редактировать данные';

    // если в запросе есть ряд (и таблица), то редактируем этот ряд
    $whereCondition = null;
    $array = POST('row');
    if (GET('row') != '') {
        $whereCondition = urldecode(stripslashes(GET('row')));
    // массовый едит
    } elseif (!is_null($array) && count($array) > 0) {
        $_POST['row'] = array_map('urldecode', array_map('stripslashes', $_POST['row']));
        $whereCondition = implode(' OR ', $_POST['row']);
    }

    // создания таблицы для данных
    $fields = getFields($msc->table);
    if ($whereCondition != null) {
        $tableData = $msc->getData('SELECT * FROM '.$msc->table.' WHERE '.$whereCondition);
        if (!$tableData) {
            $msc->notice('Ничего не выбрано');
            return;
        }
    }
}

$pageProps = [
    'table' => $msc->table,
    'db' => $msc->db,
    'fields' => $fields,
    'tableData' => $tableData,
    'msRowsInsert' => (int)MS_ROWS_INSERT,
    'dirImage' => MS_DIR_IMG,
    'isAdd' => $isAdd
];
if (isajax()) {
    return $pageProps;
}

include(MS_DIR_TPL . 'tbl_change.htm.php');