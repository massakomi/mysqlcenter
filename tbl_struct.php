<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

/**
 * Обзор стркутуры таблицы
 */

classLoad('DatabaseTable');

if (!defined('DIR_MYSQL')) {
	exit('Hacking attempt');
}
$fields = getFields($msc->table);

if (GET('action') == 'add_key') {
    $returnInAdd = true;
    if (count($_POST) > 0) {
        $keyName = POST('keyName');
        $keyDefinition = POST('keyType');
        if ($keyName != '') {
            $keyDefinition .= ' `'.$keyName.'`';
        }
        $keyFields = [];
        foreach ($_POST['field'] as $key => $fieldName) {
            if ($fieldName == '') {
                continue;
            }
            $fieldSize = $_POST['length'][$key];
            $keyFields []= '`'.$fieldName.'`'. ($fieldSize > 0 ? "($fieldSize)" : '');
        }
        $sql = 'ALTER TABLE '.$msc->table.' ADD '.$keyDefinition.' ('.implode(',', $keyFields).')';
        //var_dump($sql); exit;
		if ($msc->query($sql, $msc->db)) {
            $returnInAdd = false;
            // обновляем массив полей
            $fields = getFields($msc->table);
			$msc->addMessage('Ключ добавлен', $sql, MS_MSG_SUCCESS);
		} else {
			$msc->addMessage('Ошибка создания ключа', $sql, MS_MSG_FAULT);
		}
    }
    if ($returnInAdd) {
        $fieldRows = ['' => ''];
        foreach ($fields as $field) {
            $fieldRows [$field->Field]= "$field->Field [$field->Type]";
        }
        $msc->pageTitle = 'Добавить ключи к таблице "'.$msc->table.'"';
        include 'tpl/tbl_key_add.htm.php';
        return;
    }
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
$msc->pageTitle = 'Структура таблицы '.$msc->table;

if (GET('print') == '1') {
	echo $dbt->insertStructTable();
	exit;
}

$dataKeys = $msc->table != '' ? $msc->getData('SHOW KEYS FROM `'.$msc->table.'`') : [];

// Массив полей, распечатка массива
$fields = array_values($fields);
$tableAddStr = ['array('];
foreach ($fields as $k => $v) {
    $tableAddStr []= "&nbsp;&nbsp;&nbsp;&nbsp;'".$v->Field."' => ,";
}
$tableAddStr []= ")";
$tableAddStr = implode('<br />', $tableAddStr);

$data = $dbt->insertDetailsTable($return=1);

include(MS_DIR_TPL . 'tbl_struct.htm.php');
