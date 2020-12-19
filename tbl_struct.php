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
        $keyFields = array();
        foreach ($_POST['field'] as $key => $fieldName) {
            if ($fieldName == '') {
                continue;
            }
            $fieldSize = $_POST['length'][$key];
            $keyFields []= '`'.$fieldName.'`'. ($fieldSize > 0 ? "($fieldSize)" : '');
        }
        $sql = 'ALTER TABLE '.$msc->table.' ADD '.$keyDefinition.' ('.implode(',', $keyFields).')';
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
        $fieldRows = array('' => '');
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

// Создание таблицы
$table = new Table('contentTable');
$table -> makeRowHead('&nbsp;', 'Поле', 'Тип', 'NULL', 'По умолчанию', 'Ключ', 'Дополнительно', '&nbsp;', '&nbsp;');
$table -> setInterlace('', '#eeeeee');

// Создание рядов
$msquery = 'db='.$msc->db.'&table='.$msc->table.'&s=tbl_struct';
$fieldNames = array();
$fields = array_values($fields);
$tableAddStr = array('array(');
foreach ($fields as $k => $v) {
	$fieldNames []= $v->Field;
	$urlDelete = $msquery . '&field=' . $v->Field;
	// ключ
	$key = $v->Key;
	if ($v->Key == 'PRI') {
		$key = '<img src="'.MS_DIR_IMG.'acl.gif" alt="" border="0" />';
	}
	$table -> makeRow(array(
		'<input name="field[]" id="field'.$k.'" type="checkbox" value="'.$v->Field.'" class="cb">',
		$v->Field,
		wordwrap($v->Type, 70, '<br />', true),
		$v->Null,
		$v->Default,
		$key,
		$v->Extra,
		// ссылки
		'<a href="'.$umaker->make('s', 'tbl_add', 'field', urlencode($v->Field)).'" title="Редактировать ряд"><img src="'.MS_DIR_IMG.'edit.gif" alt="" /></a>',
		'<a href="#" onClick="msQuery(\'deleteField\', \''.$urlDelete.'&id=f-'.urlencode($v->Field).'\'); return false" title="Удалить ряд"><img src="'.MS_DIR_IMG.'close.png" alt="" /></a>'
		),
		' id="f-'.$v->Field.'"'
	);
	$tableAddStr []= "&nbsp;&nbsp;&nbsp;&nbsp;'".$v->Field."' => ,";
}
$tableAddStr []= ")";
$contentMain = $table -> make();

include(MS_DIR_TPL . 'tbl_struct.htm.php');
