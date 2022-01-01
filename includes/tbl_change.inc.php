<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */


/**
 * Общая обработка для редактирования/добавления ряда
 *
 * @package msc
 * @access private
 * @param integer Тип редактирования: 0-update, 1-insert
 */
function processRowsEdit($editType) {
    global $msc;
    if (POST('option') == 'insert') {
        $editType = 1;
    }
    $countInsert = 0;
    $fields = array_values(getFields($msc->table));
    if ($editType == 1) {
        $arrayFields = array();
        foreach ($fields as $v) {
            if (POST('option') == 'insert' && $v->Extra != null) {
                continue;
            }
            $arrayFields []= $v->Field;
        }
    }
    $lang = array(
        array('Данные обновлены', 'Данные добавлены'),
        array('Ошибка при обновлении ряда', 'Ошибка при добавлении ряда'),
        array('Ничего не обновилось', 'Ничего не добавилось')
    );
    $rows = POST('row');
    $_POST['cond'] =  POST('cond');
    foreach ($rows as $numRow => $data) {
        if ($editType == 0) {
            $where  = urldecode($_POST['cond'][$numRow]);
            $result = $msc->query('SELECT * FROM `'.$msc->table.'` WHERE '.$where);
            $cValue = mysqli_fetch_object($result);
        }
        $arrayValues = array();
        $countEmpty = 0;
        foreach ($data as $key => $value) {
            $default = $fields[$key]->Default;
            if ($default == $value) {
                $countEmpty ++;
            }
            $type = $fields[$key]->Type;
            if ($_POST['func'][$numRow][$key] != '') {
                $value = call_user_func($_POST['func'][$numRow][$key], $value);
                $type = 'varchar';
            }
            $isNull = isset($_POST['isNull'][$numRow][$key]);
            if ($editType == 0) {
                $field = $fields[$key]->Field;
                if ($value != $cValue->$field) {
                    $arrayValues []= '`'.$field. '`='.processValueType($value, $type, $isNull);
                }
            } else {
                if (POST('option') == 'insert' && $fields[$key]->Extra != null) {
                    continue;
                }
                $arrayValues []= processValueType($value, $type, $isNull);
            }
        }
        if ($countEmpty == count($data) || count($arrayValues) == 0) {
            continue;
        }
        if ($editType == 0) {
            $sql = 'UPDATE `'.$msc->table.'` SET '.implode(', ', $arrayValues).' WHERE '.$where;
        } else {
            $sql = 'INSERT INTO `'.$msc->table.'` (`'.implode('`, `', $arrayFields).'`) VALUES ('.implode(', ', $arrayValues).')';
        }
        if ($msc->query($sql)) {
            $msc->addMessage($lang[0][$editType], $sql, MS_MSG_SUCCESS);
            $countInsert ++;
        } else {
            $msc->addMessage($lang[1][$editType], $sql, MS_MSG_FAULT, $msc->error);
        }
    }
    if ($countInsert == 0) {
        $msc->addMessage($lang[2][$editType]);
    }
}

/**
 * Добавляет ряд в объект $table
 *
 * @package msc
 * @access private
 * @param object Table
 * @param string Имя поля
 * @param string Значение поля
 * @param string Номер вставляемой записи
 * @param integer Номер поля
 * @param array Массив полей таблицы
 */
/*function addRow(&$table, $name, $value, $j, $i, $fields) {
    if ($value == 'CURRENT_TIMESTAMP') {
        $value = null;
    }
    $null = '&nbsp;';
    $attr = null;
    //pre($fields[$name]);
    $type = str_replace(',', ', ', $fields[$name]->Type);
    if ($fields[$name]->Null) {
        // если нулл, то отмечаем
        $checked = null;
        if ($value == null && $fields[$name]->Null == 'YES') {
            $checked = ' checked="checked"';
        }
        $null = '<input name="isNull['.$j.']['.$i.']" type="checkbox" value="1"'.$checked.'/>';
    }
    $table -> makeRow(
        '<b class="field">'.$name.'</b><br />'.wordwrap($type, 100, '<br />'),
        $null,
        MSC_InsertInput($j, $type, $value, false, $attr, $i),
        plDrawSelector(array('','md5'), ' name="func['.$j.']['.$i.']"', '', '', false)
    );
}*/

/**
 * Возвращает поле формы в зависимости от номера и типа поля
 *
 * @package msc
 * @access private
 * @param integer Номер поля
 * @param string Тип поля
 * @param string Значение поля
 * @param boolean Дифференцировать ли длину поля в зависимости от его длины(length)
 * @param string Аттрибуты, которые необходимо добавить к тегу поля формы
 * @param string Номер вставляемой записи
 * @return string HTML код поля формы
 */
/*function MSC_InsertInput($i, $type, $value=null, $diffLength=true, $attr=null, $j=null) {
    //$value = $type;
    global $msc;
    $length = null;
    if (preg_match('/\(([0-9]+)\)/', $type, $a)) {
        $length = intval($a[1]);
    }
    if ($length == 1) {
        //return '<input name="row['.$i.'][]" type="checkbox" value="1" />';
    }
    if (stristr($type, 'enum')) {
        preg_match_all('~(\'|")(.*)(\'|")~iU', $type, $items);
        if (isset($items[2])) {
            array_unshift($items[2], '');
            // убираем двойные пробелы
            foreach ($items[2] as $k => $v) {
                $items[2][$k] = preg_replace('~\s+~i', ' ', $v);
            }
            $value = preg_replace('~\s+~i', ' ', $value);
            $attr = str_replace('onkeyup', 'onchange', $attr);
            //var_dump($value);
            //echo '<pre>'; print_r($items[2]); echo '</pre>';
            //var_dump(array_search($value, $items[2]));
            return plDrawSelector(
                $items[2],
                ' name="row['.$i.']['.$j.']"'.$attr,
                array_search($value, $items[2]),
                '',
                false
            );
        }
    }

    // текст
    if (stristr($type, 'text') || stristr($type, 'blob')) {
        $rows = 10;
        if (!is_null($value)) {
            $rows = round(strlen($value) / 60);
            if ($rows < 10) {
                $rows = 10;
            }
        }
        return '<textarea name="row['.$i.']['.$j.']" cols="70" rows="'.$rows.'"'.$attr.'>'.$value.'</textarea>';
    }
    // числа
    $size = 80;
    if ($diffLength) {
        if ($length <= 15) {
            $size = $length;
        } else if ($length < 30) {
            $size = round($length / 1.2);
        } else if ($length >= 30) {
            $size = round($length / 3);
        }
    } else {
        if ($length <= 15) {
            $size = 15;
        } else if ($length < 128) {
            $size = 50;
        } else {
            $size = 80;
        }
    }
    if ($type == 'datetime') {
        //$value = date('Y-m-d H:i:s');
        $size = 30;
    }
    // дата
    if ($type == 'timestamp' && $value != null) {
        $size = 50;
        //return plDrawDateSelector(strtotime($value), 'date'.$i.'_');
    }
    $value = htmlspecialchars($value);
    return '<input name="row['.$i.']['.$j.']" type="text" size="'.$size.'" value="'.$value.'" class="si"'.$attr.'/>';
}*/

