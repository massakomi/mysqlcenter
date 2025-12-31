<?php

declare(strict_types=1);

namespace controller;

use database\Table;

/**
 *
 */
class TblAdd extends Base
{
    public function defaultAction(): array
    {
        global $msc;

        // Получаем начальную инфо о полях таблицы
        $fields = [];
        if ($msc->table) {
            $fields = Table::getFields($msc->table);
            if (!$fields) {
                $msc->error('Таблица не найдена');
                return [];
            }
        }

        // HTML форма
        // Создание таблицы или добавление полей
        $array = [];
        $afterSql = '';
        if ($msc->table == null || POST('action') == 'fieldsAdd') {
            $numFields = POST('numFields', GET('fieldsNum', MS_FIELDS_COUNT));
            $fieldsCount = isset($_POST['name']) ? count($_POST['name']) : $numFields;
            if (is_array(POST('name'))) {
            } else {
                $array = range(0, $fieldsCount - 1);
            }
            $msc->pageTitle = "Добавить таблицу в базу данных $msc->db";
            // Добавление полей
            if (POST('action') == 'fieldsAdd') {
                $msc->pageTitle = 'Добавить поля';
                if (POST('afterOption') == 'start') {
                    $afterSql = 'FIRST';
                } elseif (POST('afterOption') == 'field') {
                    $afterSql = 'AFTER `' . POST('afterField') . '`';
                }
            }

        // Редактирование полей или таблицы
        } else {
            // TODO весь этот блок тоже в js
            // редактируемые поля
            $edited = [];
            if (GET('field') != '') {
                $edited [] = stripslashes(urldecode(GET('field')));
            } elseif (!empty($_POST['fields'])) {
                $edited = explode(',', $_POST['fields']);
            } elseif (isset($_POST['field']) && count($_POST['field']) > 0) {
                $edited = $_POST['field'];
            }
            // собираем только те поля, которые реально существуют в таблице
            foreach ($fields as $row) {
                if (count($edited) > 0) {
                    if (in_array($row->Field, $edited)) {
                        $array [] = $row;
                    }
                    continue;
                }
                $array [] = $row;
            }
            $msc->pageTitle = 'Редактировать структуру';
        }

        $getAction = function () {
            if (GET('s') == 'tbl_add' && empty($_POST) && !isset($_GET['field'])) {
                return 'tableAddEnd';
            } elseif (POST('action') == 'fieldsAdd') {
                return 'fieldsAddEnd';
            }
            return 'fieldsEditEnd';
        };

        $showTableName = function () {
            return POST('action') != 'fieldsAdd' && !isset($_GET['field']) && POST('action') != 'fieldsEdit';
        };

        return [
            'dirImage' => MS_DIR_IMG,
            'action' => $getAction(),
            'afterSql' => $afterSql,
            'showTableName' => $showTableName(),
            'tableName' => POST('tableName') ?: $msc->table,
            'array' => $array,
            'post' => $_POST,
            'keys' => Table::getTableKeys($msc->table),
            'fields' => Table::getFields($msc->table, true)
        ];
    }

    /**
     * {}
     */
    public function process($names, $fields, &$afterSql): array
    {
        global $msc;
        // Ключи
        $uk = $_POST['uni'] ?? [];
        $mk = $_POST['mul'] ?? [];
        if (POST('action') == 'tableAddEnd' && is_numeric(POST('primaryKey'))) {
            // при добавлении таблицы вместо имён полей у нас только индексы полей, которые создаются в таблице
            // поэтому приходится создавать массивы ключей самостоятельно
            $primaryKey = $names[POST('primaryKey')];
            $uniKeys    = [];
            $mulKeys    = [];
            foreach ($names as $k => $name) {
                if (array_key_exists($k, $uk)) {
                    $uniKeys [] = $name;
                }
                if (array_key_exists($k, $mk)) {
                    $mulKeys [] = $name;
                }
            }
        } else {
            $primaryKey = POST('primaryKey');
            $uniKeys = $uk;
            $mulKeys = $mk;
        }
        $fieldsDefFull  = []; // field    definition
        $fieldsDefEdit  = []; // oldfield definition
        $newKeys        = [];
        foreach ($_POST as $k => $v) {
            $_POST [$k] = POST($k);
        }
        foreach ($names as $k => $name) {
            if (empty($name)) {
                continue;
            }
            $type    = $_POST['ftype'][$k];
            $null    = (isset($_POST['isNull'][$k]));
            $default = $_POST['default'][$k];
            $extra   = (isset($_POST['auto'][$k])) ? 'AUTO_INCREMENT' : null;
            $extra  .= $_POST['attr'][$k] != '' ? ' ' . $_POST['attr'][$k] : null;
            $length  = $_POST['length'][$k];
            $define  = Table::getFieldDefinition($type, $null, $default, $extra, $length);
            if (empty($define)) {
                $msc->error('Не удалось создать поле "' . $name . '". Не указаны дополнительные параметры поля');
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
                    $define .= ' AFTER `' . $after . '`';
                }
            }
            $fieldsDefFull [] = "`$name` $define";
            if (POST('action') == 'fieldsEditEnd') {
                $fieldsDefEdit [$_POST['oldname'][$k]] = '`' . $name . '` ' . $define;
            }
            // ключи
            if (array_key_exists($name, $uniKeys)) {
                $newKeys [] = "$name UNI " . $uniKeys[$name];
            }
            if (array_key_exists($name, $mulKeys)) {
                $newKeys [] = "$name MUL " . $mulKeys[$name];
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
            if ($msc->execPdo($sql)) {
                $msc->success('Таблица ' . POST('table_name') . ' создана', $sql);
            } else {
                $text = 'При создании таблицы возникли ошибки ' . POST('table_name');
                $msc->error($text, $sql);
            }
        }
        // создание запроса на изменение полей
        if (POST('action') == 'fieldsEditEnd') {
            // определение полей
            $a = [];
            foreach ($fieldsDefEdit as $oldFieldName => $def) {
                $oldDefinition = "`$oldFieldName` " . Table::getFieldDefinition($fields[$oldFieldName]);
                //echo "<br />$oldDefinition == $def";exit;
                if ($oldDefinition == $def) {
                    continue;
                }
                $a [] = ' CHANGE `' . $oldFieldName . '` ' . $def;
            }
            $sql  = count($a) == 0 ? '' : 'ALTER TABLE `' . GET('table') . "`\r\n" . implode(",\r\n", $a);
            // ключи
            $currentKeys = [];
            $a = Table::getTableKeys($msc->table);
            $currentPrimaryKey = '';
            foreach ($a as $fieldName => $currentKeyNames) {
                foreach ($currentKeyNames as $k => $currentKeyName) {
                    if ($currentKeyName != 'PRI') {
                        if (!in_array($fieldName, $names)) {
                            continue;
                        }
                        $currentKeys [] = "$fieldName $currentKeyName $k";
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
            $sql2 = [];
            foreach ($currentKeys as $k => $removeKey) {
                list($fieldName, $removeKeyType, $removeKeyName) = explode(' ', $removeKey);
                $sql2 [] = 'ALTER TABLE `' . GET('table') . '` DROP KEY `' . $removeKeyName . '`';
            }
            foreach ($newKeys as $k => $addKey) {
                list($fieldName, $addKeyType, $addKeyName) = explode(' ', $addKey);
                $str = $addKeyType == 'UNI' ? 'UNIQUE' : 'INDEX';
                $sql2 [] = 'ALTER TABLE `' . GET('table') . '` ADD ' . $str . ' (`' . $fieldName . '`)';
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
                            Table::dropPrimaryKey(GET('table'));
                        }
                    } else {
                        Table::dropPrimaryKey(GET('table'));
                    }
                }
                if ($primaryKey != '') {
                    $sql2 [] = 'ALTER TABLE `' . GET('table') . '` ADD PRIMARY KEY (`' . $primaryKey . '`)';
                }
            }

            foreach ($sql2 as $s) {
                if ($msc->execPdo($s)) {
                    $msc->success('Ключи изменены', $s);
                } else {
                    $msc->error('Ошибка при изменении ключей', $s);
                }
            }
            // выполнение
            if ($sql != '') {
                if ($msc->execPdo($sql)) {
                    $msc->success('Таблица изменена', $sql);
                } else {
                    $msc->error('Ошибка при изменении полей', $sql);
                }
            } else {
                $msc->notice('В definition ничего не изменилось', '');
            }
        }
        // создание запроса на добавление
        if (POST('action') == 'fieldsAddEnd') {
            if (POST('afterOption') == 'start') {
                $afterSql = 'FIRST';
            } elseif (POST('afterOption') == 'field') {
                $afterSql = 'AFTER `' . POST('afterField') . '`';
            }
            // определение полей
            $a = [];
            foreach ($fieldsDefFull as $def) {
                $a [] = ' ADD COLUMN ' . $def . $afterSql;
            }
            $sql  = 'ALTER TABLE `' . GET('table')  . "`\r\n" . implode(",\r\n", $a);
            // ключи
            $oldFields = Table::getFields(GET('table'));
            // выполнение
            if ($msc->execPdo($sql)) {
                $msc->success('Таблица изменена', $sql);
            } else {
                $msc->error('Ошибка при добавлении полей');
            }
        }
        if (isAjax()) {
            ajaxResultWithMessages();
        }
        return [];
    }
}
