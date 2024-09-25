<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Обзор стркутуры таблицы
 */

classLoad('DatabaseTable');

if (!defined('DIR_MYSQL')) {
    exit('Hacking attempt');
}
$fields = getFields($msc->table);

if (GET('mode') == 'add_key') {
     $fieldRows = ['' => ''];
    foreach ($fields as $field) {
        $fieldRows [$field->Field] = "$field->Field [$field->Type]";
    }
    $msc->pageTitle = 'Добавить ключи к таблице "' . $msc->table . '"';

    $pageProps = [
        'fieldRows' => $fieldRows,
        'keyName' => POST('keyName'),
        'postType' => POST('keyType'),
        'dirImage' => MS_DIR_IMG,
    ];
    if (isajax()) {
        return $pageProps;
    }

    include 'tpl/tbl_key_add.htm.php';
    return;
}


if ($msc->table == '') {
    $msc->pageTitle = NULL;
    $msc->addMessage('Не указана таблица в запросе', null, MS_MSG_FAULT);
    return null;
}
$dbt = new DatabaseTable($msc->db, $msc->table);
if (!$dbt->isExists()) {
    $msc->pageTitle = NULL;
    $msc->addMessage("Таблицы $msc->table не существует", null, MS_MSG_FAULT);
    return null;
}
$msc->pageTitle = 'Структура таблицы ' . $msc->table;

if (GET('print') == '1') {
    echo $dbt->insertStructTable();
    exit;
}

$a = $msc->table != '' ? $msc->getData('
SELECT i.*, k.*  FROM information_schema.TABLE_CONSTRAINTS i
LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME 
WHERE  i.CONSTRAINT_TYPE = \'FOREIGN KEY\' AND i.TABLE_SCHEMA = \'' . $msc->db . '\'
AND i.TABLE_NAME = \'' . $msc->table . '\'
GROUP BY k.CONSTRAINT_NAME
') : [];
$foreignKeys = [];
foreach ($a as $k => $v) {
    $foreignKeys[$v['COLUMN_NAME']] = $v;
}
$dataKeys = $msc->table != '' ? $msc->getData('SHOW KEYS FROM `' . $msc->table . '`') : [];

// Массив полей, распечатка массива
$fields = array_values($fields);
$tableAddStr = ['array('];
foreach ($fields as $k => $v) {
    $tableAddStr [] = "&nbsp;&nbsp;&nbsp;&nbsp;'" . $v->Field . "' => ,";
}
$tableAddStr [] = ")";
$tableAddStr = implode('<br />', $tableAddStr);

$data = $dbt->insertDetailsTable($return = 1);

$pageProps = [
    'db' => $msc->db,
    'table' => $msc->table,
    'addKeyUrl' => $umaker->make('s', 'tbl_struct', 'action', 'add_key'),
    'addTableUrl' => $umaker->make('s', 'tbl_add'),
    'printVersionUrl' => $umaker->make('s', 'tbl_struct', 'print', '1'),
    'data' => $fields,
    'dataKeys' => $dataKeys,
    'foreignKeys' => $foreignKeys,
    'dataDetails' => $data,
    'tableAddStr' => "<div>$tableAddStr</div>",
    'dirImage' => MS_DIR_IMG
];
if (isajax()) {
    return $pageProps;
}

include(MS_DIR_TPL . 'tbl_struct.htm.php');
