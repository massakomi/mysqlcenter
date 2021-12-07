<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

if (!defined('DIR_MYSQL')) { 
	exit('Hacking attempt');
}
if ($msc->table == '') {
	exit('Не указана таблица в запросе');
}

require_once DIR_MYSQL . 'includes/tbl_change.inc.php';

$tableData = [];

/**
 * Вставка рядов
 */
if (GET('row') == '' && POST('row') == '') {


    $msc->pageTitle = 'Добавить строки в таблицу';
    $fields = getFields($msc->table);
    $isAdd = true;

    /*$head = array();
    $contentMain = '<table id="tableDataAdd">';
    for ($i = 0; $i < MS_ROWS_INSERT; $i ++) {
      $table = new Table();
      // Внимание в таблице SPAN должен быть один, иначе надо менять код JS ниже
        $table -> makeRow(array('Поле', 'Ноль', 'Ряд #<span>'.($i + 1).'</span>', 'Функция'), ' class="editHeader"');
        $j = 0;
        foreach ($fields as $k => $row) {
            addRow($table, $row->Field, $row->Default, $i, $j, $fields);
            $j ++;
        }
        $contentMain .= '<tr><td class="inner">'.$table->make().'</td></tr>';
    }

    $contentMain .= '</table>';*/

    //echo $contentMain;

}


/**
 * Изменение рядов
 */
else {

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
    $contentMain = null;
    $fields = getFields($msc->table);
    if ($whereCondition != null) {
        $result = $msc->query('SELECT * FROM '.$msc->table.' WHERE '.$whereCondition);
        if (!$result) {
            $msc->notice('Ничего не выбрано');
            return;
        }
        /*$fieldsNames = array();
        foreach ($fields as $v) {
            $fieldsNames []= $v->Field;
        }
        $j = 0;
        $table = new Table();
        $contentMain = '';*/
        while ($data = mysqli_fetch_object($result)) {

            $tableData []= $data;

            /*$i = 0;
            $table->makeRow(array('Поле', 'Ноль', 'Ряд #' . ($j + 1), 'Функция'), ' class="editHeader"', '');
            $pk = array();
            foreach ($data as $name => $value) {
                $key  = $fields[$name]->Key;
                if (strchr($key, 'PRI')) {
                    $pk []= $name.'="'.$value.'"';
                }
                addRow($table, $name, $value, $j, $i, $fields);
                $i ++;
            }
            if (count($pk) == 0) {
                foreach ($fieldsNames as $v) {
                    if ($data->$v == null) {
                        continue;
                    }
                    $pk []= $v.'="'.$data->$v.'"';
                }
            }
            // последний ряд -
            $cond = urlencode(implode(' AND ', $pk));
            $contentMain .= '<input name="cond[]" type="hidden" value="'.$cond.'">';
            $j ++;*/
        }
        //$contentMain .= $table->make();
    }

    // выводим форму только если непустой контент
    /*if ($contentMain != null) {

    }*/
}


include(MS_DIR_TPL . 'tbl_change.htm.php');