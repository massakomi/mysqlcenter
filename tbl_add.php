<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Скрипт создание таблицы в БД
 */

if (!defined('DIR_MYSQL')) {
    exit('Hacking attempt');
}

// Получаем начальную инфо о полях таблицы
$fields = getFields($msc->table);

// Получаем массив имён полей из формы.
$names = POST('name');
if (is_array($names) && count($names) > 0 && POST('action') != '') {
    // Ключи
    $uk = $_POST['uni'] ?? [];
    $mk = $_POST['mul'] ?? [];
    if (POST('action') == 'tableAddEnd' && is_numeric(POST('primaryKey'))) {
        // при добавлении таблицы вместо имён полей у нас только индексы полей, которые создаются в таблице
        // поэтому приходится создавать массивы ключей самостоятельно
        $primaryKey = $names[POST('primaryKey')];
        $uniKeys    = array();
        $mulKeys    = array();
        foreach ($names as $k => $name) {
            if (array_key_exists($k, $uk)) {
                $uniKeys []= $name;
            }
            if (array_key_exists($k, $mk)) {
                $mulKeys []= $name;
            }
        }
    } else {
        $primaryKey = POST('primaryKey');
        $uniKeys = $uk;
        $mulKeys = $mk;
    }
    $fieldsDefFull  = array(); // field    definition
    $fieldsDefEdit  = array(); // oldfield definition
    $newKeys        = array();
    foreach ($_POST as $k => $v) {
        $_POST [$k]= POST($k);
    }
    foreach ($names as $k => $name) {
        if (empty($name)) {
            continue;
        }
        $type    = $_POST['ftype'][$k];
        $null    = (isset($_POST['isNull'][$k]));
        $default = $_POST['default'][$k];
        $extra   = (isset($_POST['auto'][$k])) ? 'AUTO_INCREMENT' : null;
        $extra  .= $_POST['attr'][$k] != '' ? ' '.$_POST['attr'][$k] : null;
        $length  = $_POST['length'][$k];
        $define  = getFieldDefinition($type, $null, $default, $extra, $length);
        if (empty($define)) {
            $msc->addMessage('Не удалось создать поле "'.$name.'". Не указаны дополнительные параметры поля',
                '', MS_MSG_FAULT);
            unset($names[$k]);
            continue;
        }
        // after
        $after    = $_POST['after'][$k];
        $afterold = $_POST['afterold'][$k];
        if ($after != $afterold) {
            if ($after == 'FIRST') {
                $define .= ' FIRST';
            } else {
                $define .= ' AFTER `'.$after.'`';
            }
        }
        $fieldsDefFull []= "`$name` $define";
        if (POST('action') == 'fieldsEditEnd') {
            $fieldsDefEdit [$_POST['oldname'][$k]]= '`'.$name .'` '. $define;
        }
        // ключи
        if (array_key_exists($name, $uniKeys)) {
            $newKeys []= "$name UNI ".$uniKeys[$name];
        }
        if (array_key_exists($name, $mulKeys)) {
            $newKeys []= "$name MUL ".$mulKeys[$name];
        }
    }
    // TODO обработка SET ENUM полей

    // создание запроса на сздание таблицы
    if (POST('action') == 'tableAddEnd') {
        $sql  = "CREATE TABLE `" . POST('table_name') . "` (\r\n  ";
        $sql .= implode(",\r\n  ", $fieldsDefFull);
        if ($primaryKey != '') {
            $sql .= ",\r\n  PRIMARY KEY ($primaryKey)";
        }
        if (count($uniKeys) > 0) {
            $sql .= ",\r\n  UNIQUE (" . implode(', ', $uniKeys) . ")";
        }
        if (count($mulKeys) > 0) {
            $sql .= ",\r\n  INDEX (" . implode(', ', $mulKeys) . ")";
        }
        $sql .= "\r\n)";
        if ($msc->query($sql)) {
            $msc->addMessage('Таблица '.POST('table_name').' создана', $sql, MS_MSG_SUCCESS);
            if (!isajax()) {
                $msc->table = POST('table_name');
                $msc->pageTitle = 'Обзор таблицы ' . POST('table_name');
                include_once(DIR_MYSQL . 'tbl_data.php');
                return null;
            }
        } else {
            $msc->addMessage('При создании таблицы возникли ошибки '.POST('table_name'), $sql, MS_MSG_NOTICE, mysqli_errorx());
        }
    }
    // создание запроса на изменение полей
    if (POST('action') == 'fieldsEditEnd') {
        // определение полей
        $a = array();
        foreach ($fieldsDefEdit as $oldFieldName => $def) {
            $oldDefinition = "`$oldFieldName` ".getFieldDefinition($fields[$oldFieldName]);
            //echo "<br />$oldDefinition == $def";exit;
            if ($oldDefinition == $def) {
                continue;
            }
            $a []= ' CHANGE `'.$oldFieldName.'` '. $def;
        }
        $sql  = count($a) == 0 ? '' : 'ALTER TABLE `'.GET('table') . "`\r\n" . implode(",\r\n", $a);
        // ключи
        $currentKeys = array();
        $a = getTableKeys($msc->table);
        $currentPrimaryKey = '';
        foreach ($a as $fieldName => $currentKeyNames) {
            foreach ($currentKeyNames as $k => $currentKeyName) {
                if ($currentKeyName != 'PRI') {
                    if (!in_array($fieldName, $names)) {
                        continue;
                    }
                    $currentKeys []= "$fieldName $currentKeyName $k";
                } else {
                    $currentPrimaryKey = $fieldName;
                }
            }
        }
        // удаляем пересекающиеся ключи
        foreach ($currentKeys as $currentKey) {
            if (in_array($currentKey, $newKeys)) {
                unset($newKeys[array_search($currentKey, $newKeys)]);
                unset($currentKeys[array_search($currentKey, $currentKeys)]);
            }
        }
        $sql2 = array();
        foreach ($currentKeys as $k => $removeKey) {
            list($fieldName, $removeKeyType, $removeKeyName) = explode(' ', $removeKey);
            $sql2 []= 'ALTER TABLE `'.GET('table').'` DROP KEY `'.$removeKeyName.'`';
        }
        foreach ($newKeys as $k => $addKey) {
            list($fieldName, $addKeyType, $addKeyName) = explode(' ', $addKey);
            $sql2 []= 'ALTER TABLE `'.GET('table').'` ADD '.($addKeyType=='UNI'?'UNIQUE':'INDEX').' (`'.$fieldName.'`)';
        }
        if ($currentPrimaryKey != $primaryKey) {
            if ($currentPrimaryKey != '') {
                if ($primaryKey == '') {
                    $drop = false;
                    // если в числе обновлённых полей нет текущего primary key, то не удалям ключ
                    foreach ($names as $k => $name) {
                        if ($currentPrimaryKey == $name) {
                            $drop = true;
                        }
                    }
                    if ($drop) {
                        dropPrimaryKey(GET('table'));
                    }
                } else {
                    dropPrimaryKey(GET('table'));
                }
            }
            if ($primaryKey != '') {
                $sql2 []= 'ALTER TABLE `'.GET('table').'` ADD PRIMARY KEY (`'.$primaryKey.'`)';
            }
        }

        foreach ($sql2 as $s) {
            if ($msc->query($s)) {
                $msc->addMessage('Ключи изменены', $s, MS_MSG_SUCCESS);
            } else {
                $msc->addMessage('Ошибка при изменении ключей', $s, MS_MSG_FAULT, mysqli_errorx());
            }
        }
        // выполнение
        if ($sql != '') {
            if ($msc->query($sql)) {
                $msc->addMessage('Таблица изменена', $sql, MS_MSG_SUCCESS);
                if (!isajax()) {
                    include DIR_MYSQL . 'tbl_struct.php';
                    return null;
                }
            } else {
                $msc->addMessage('Ошибка при изменении таблицы', $sql, MS_MSG_FAULT, mysqli_errorx());
            }
        } else {
            $msc->addMessage('В definition ничего не изменилось', '', MS_MSG_NOTICE);
            if (!isajax()) {
                include DIR_MYSQL . 'tbl_struct.php';
                return null;
            }
        }
    }
    // создание запроса на добавление
    if (POST('action') == 'fieldsAddEnd') {
        // определение полей
        $a = array();
        foreach ($fieldsDefFull as $def) {
            $a []= ' ADD COLUMN ' . $def . $afterSql;
        }
        $sql  = 'ALTER TABLE `' . GET('table')  . "`\r\n" . implode(",\r\n", $a);
        // ключи
        $oldFields = getFields(GET('table'));
        // выполнение
        if ($msc->query($sql)) {
            $msc->addMessage('Таблица изменена', $sql, MS_MSG_SUCCESS);
            if (!isajax()) {
                include DIR_MYSQL . 'tbl_struct.php';
                return null;
            }
        } else {
            $msc->addMessage('Ошибка при изменении таблицы', $sql, MS_MSG_FAULT, mysqli_errorx());
        }
    }
    if (isajax()) {
        ajaxResultWithMessages();
    }
}

// HTML форма
// Создание таблицы или добавление полей
if ($msc->table == null || POST('action') == 'fieldsAdd') {
    $fieldsCount = isset($_POST['name']) ? count($_POST['name']) : POST('numFields', GET('fieldsNum', MS_FIELDS_COUNT));
    if (is_array(POST('name'))) {
        //$cont = MSC_DrawFields();
    } else {
        $array = range(0, $fieldsCount - 1);
        //$cont = MSC_DrawFields($array);
    }
    $msc->pageTitle = "Добавить таблицу в базу данных $msc->db";
    // Добавление полей
    if (POST('action') == 'fieldsAdd') {
        $msc->pageTitle = 'Добавить поля';
        if (POST('afterOption') == 'start') {
            $afterSql = 'FIRST';
        } elseif (POST('afterOption') == 'field') {
            $afterSql = 'AFTER `'.POST('afterField').'`';
        }
    }

// Редактирование полей или таблицы
} else {

    // TODO весь этот блок тоже в js
    // редактируемые поля
    $edited = array();
    if (GET('field') != '') {
        $edited []= stripslashes(urldecode(GET('field')));
    } elseif (!empty($_POST['fields'])) {
        $edited = explode(',', $_POST['fields']);
    } elseif (isset($_POST['field']) && count($_POST['field']) > 0) {
        $edited = $_POST['field'];
    }
    // собираем только те поля, которые реально существуют в таблице
    $array = array();
    foreach ($fields as $row) {
        if (count($edited) > 0) {
            if (in_array($row->Field, $edited)) {
                $array []= $row;
            }
            continue;
        }
        $array []= $row;
    }
    if (count($array) == 0 && !isajax()) {
        redirect('?s=tbl_List');
    }

    //$cont = MSC_DrawFields($array);
    $msc->pageTitle = 'Редактировать структуру';
}

$pageProps = [
    'dirImage' => MS_DIR_IMG,
    'action' => GET('s')=='tbl_add'&&empty($_POST)&&!isset($_GET['field'])?'tableAddEnd':(POST('action') == 'fieldsAdd'?'fieldsAddEnd':'fieldsEditEnd'),
    'afterSql' => $afterSql,
    'showTableName' => POST('action') != 'fieldsAdd'&&!isset($_GET['field'])&&$_POST['action'] != 'fieldsEdit',
    'tableName' => POST('tableName') ?: $msc->table,
    'array' => $array,
    'post' => $_POST,
    'keys' => getTableKeys($msc->table),
    'fields' => getFields($msc->table, true)
];
if (isajax()) {
    return $pageProps;
}

include(MS_DIR_TPL . 'tbl_edit.htm.php');
