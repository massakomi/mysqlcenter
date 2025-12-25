<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Обзор стркутуры таблицы
 */

if (!defined('DIR_MYSQL')) {
    exit('Hacking attempt');
}
$fields = getFields($msc->table);


if (GET('action') == 'add_key') {
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

    $this->template($pageProps);
    return;
}


if ($msc->table == '') {
    $msc->addMessage('Не указана таблица в запросе', null, MS_MSG_FAULT);
    return null;
}
$dbt = new DatabaseTable($msc->db, $msc->table);
if (!$dbt->isExists()) {
    $msc->addMessage("Таблицы $msc->table не существует", null, MS_MSG_FAULT);
    return null;
}
$msc->pageTitle = 'Структура таблицы ' . $msc->table;


function getKeys() {
    global $msc;
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
    return [$dataKeys, $foreignKeys];
}
$dataKeys = $foreignKeys = [];
if (GET('keys')) {
    [$dataKeys, $foreignKeys] = getKeys();
}

$sqlCreateTable = '';
$res = $msc->fetchPdo('SHOW CREATE TABLE '.$msc->table);
if ($res) {
    $sqlCreateTable = $res->fetch()['Create Table'];
}


$data = $dbt->insertDetailsTable();

$pageProps = [
    'db' => $msc->db,
    'table' => $msc->table,
    'addKeyUrl' => $umaker->make('s', 'tbl_struct', 'action', 'add_key'),
    'showKeysUrl' => $umaker->make('s', 'tbl_struct', 'keys', 1),
    'addTableUrl' => $umaker->make('s', 'tbl_add'),
    'data' => $fields,
    'dataKeys' => $dataKeys,
    'foreignKeys' => $foreignKeys,
    'dataDetails' => $data,
    'sqlCreateTable' => $sqlCreateTable,
    'dirImage' => MS_DIR_IMG
];
if (isajax()) {
    return $pageProps;
}
$this->template($pageProps);
