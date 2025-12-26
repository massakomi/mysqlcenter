<?php

/**
 * Возвращает таблицу с полной информацией о таблице $this->table
 *
 * @param $table
 * @return array
 */
function insertDetailsTable($table): array
{
    global $msc;
    $comments = [
        'Engine' => ' title="Тип хранилища"',
        'Version' => ' title="Версия .frm файла таблицы"',
        'Row_format' => ' title="Формат хранения строки (Fixed, Dynamic, Compressed, Redundant, Compact). Начиная с MySQL/InnoDB 5.0.3, InnoDB таблицы хранятся в форматах Redundant или Compact. До 5.0.3, InnoDB таблицы всегда были в формате Redundant"',
        'Rows' => ' title="Количество рядов. Некоторые типы хранилищ, такие как MyISAM, отображают точное количество. Но в некоторых других, таких как InnoDB, это значение является приблизительным и может отличаться от действительного количество на 40-50%. В таких случаях лучше всего использовать запрос SELECT COUNT(*). Также это значение равно NULL для таблиц INFORMATION_SCHEMA базы данных"',
        'Avg_row_length' => ' title="Средняя длина строки"',
        'Data_length' => ' title="Размер файла данных таблицы"',
        'Max_data_length' => ' title="Максимальный размер файла данных. Это общее количество байтов данных, которое может быть сохранено в таблице, given the data pointer size used."',
        'Index_length' => ' title="Размер индексного файла"',
        'Data_free' => ' title="Размер занятого, но не использованного пространства"',
        'Auto_increment' => ' title="Следующее значение поля Auto_increment"',
        'Update_time' => ' title="Когда дата файл был обновлён. Для некоторых типов хранилищ, это значение NULL. Например, InnoDB хранит таблицы в собственном хранилище и время изменения файла данных не даст ничего"',
        'Check_time' => ' title="Когда таблицы были проверены в последний раз. Не все типы хранилищ обновляют этот параметр, в этих случаях он всегда NULL"',
        'Collation' => ' title="Кодировка и сравнение таблиц"',
        'Checksum' => ' title="The live checksum value (if any)."',
        'Create_options' => ' title="Дополнительные опции, заданные при создании таблицы через CREATE TABLE."',
        'Comment' => ' title="Комментарий, заданный при создании таблицы (либо информация о том, почему MySQL не может получить доступ к информации о таблице"'
    ];
    $sql = 'SHOW TABLE STATUS LIKE "' . $table .'"' ;

    $result = $msc->getData($sql);
    foreach ($comments as $k => &$v) {
        $v = str_replace('"', '', $v);
        $v = str_replace(' title=', '', $v);
    }
    if (isajax()) {
        return $result[0];
    }
    return [$comments, $result];
}

if (!defined('DIR_MYSQL')) {
    exit('Hacking attempt');
}


if (GET('action') == 'add_key') {
    $fieldRows = ['' => ''];
    $fields = getFields($msc->table);
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
$fields = getFields($msc->table);
if (!$fields) {
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


$data = insertDetailsTable($msc->table);

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
