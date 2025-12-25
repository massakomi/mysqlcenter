<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
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
            $cValue = $msc->fetchPdo('SELECT * FROM `'.$msc->table.'` WHERE '.$where)->fetchObject();
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
            if ($isNull) {
                $value = null;
            }
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
        if ($msc->execPdo($sql)) {
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
