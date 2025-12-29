<?php

namespace controller;

/**
 * Вставка/изменение рядов
 */
class TblChange extends Base
{
    public function defaultAction(): array
    {
        global $msc, $umaker;
        if ($msc->table == '') {
            $msc->error('Не указана таблица в запросе');
            return [];
        }

        if ($_POST) {
            $this->processRowsEdit($_POST['action'] == 'rowsEdit' ? 0 : 1);
        }

        $tableData = [];
        $fields = \DatabaseTable::getFields($msc->table);

        if (GET('row') == '' && POST('row') == '') {
            $msc->pageTitle = 'Добавить строки в таблицу';
            $isAdd = true;
        } else {
            $msc->pageTitle = 'Редактировать данные';
            $whereCondition = $this->whereCondition();
            if ($whereCondition != null) {
                $tableData = $msc->getData('SELECT * FROM ' . $msc->table . ' WHERE ' . $whereCondition);
                if (!$tableData) {
                    $msc->error('Ничего не выбрано');
                    return [];
                }
            }
        }

        $pageProps = [
            'table' => $msc->table,
            'db' => $msc->db,
            'fields' => $fields,
            'tableData' => $tableData,
            'msRowsInsert' => (int)MS_ROWS_INSERT,
            'dirImage' => MS_DIR_IMG,
            'isAdd' => $isAdd,
        ];
        if (POST('redirect')) {
            $pageProps['redirect'] = $umaker->make('s', POST('redirect'));
        }
        return $pageProps;
    }

    /**
     * Если в запросе есть ряд (и таблица), то редактируем этот ряд
     */
    private function whereCondition(): ?string
    {
        $whereCondition = null;
        $array = POST('row');
        if (GET('row') != '') {
            $whereCondition = urldecode(stripslashes(GET('row')));
            // массовый едит
        } elseif (!is_null($array)) {
            if (!is_array($array)) {
                $array = explode(',', $array);
            }
            if (count($array) > 0) {
                $array = array_map('urldecode', array_map('stripslashes', $array));
                $whereCondition = implode(' OR ', $array);
            }
        }
        return $whereCondition;
    }

    /**
     * Общая обработка для редактирования/добавления ряда
     *
     * @param integer Тип редактирования: 0-update, 1-insert
     * @package msc
     * @access private
     */
    private function processRowsEdit($editType)
    {
        global $msc;
        if (POST('option') == 'insert') {
            $editType = 1;
        }
        $countInsert = 0;
        $fields = array_values(\DatabaseTable::getFields($msc->table));
        if ($editType == 1) {
            $arrayFields = [];
            foreach ($fields as $v) {
                if (POST('option') == 'insert' && $v->Extra != null) {
                    continue;
                }
                $arrayFields [] = $v->Field;
            }
        }
        $lang = [
            ['Данные обновлены', 'Данные добавлены'],
            ['Ошибка при обновлении ряда', 'Ошибка при добавлении ряда'],
            ['Ничего не обновилось', 'Ничего не добавилось']
        ];
        $rows = POST('row');
        $_POST['cond'] = POST('cond');
        foreach ($rows as $numRow => $data) {
            if ($editType == 0) {
                $where = urldecode($_POST['cond'][$numRow]);
                $cValue = $msc->fetchPdo('SELECT * FROM `' . $msc->table . '` WHERE ' . $where)->fetchObject();
            }
            $arrayValues = [];
            $countEmpty = 0;
            foreach ($data as $key => $value) {
                $default = $fields[$key]->Default;
                if ($default == $value) {
                    $countEmpty++;
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
                        $arrayValues [] = '`' . $field . '`=' . $this->processValueType($value, $type, $isNull);
                    }
                } else {
                    if (POST('option') == 'insert' && $fields[$key]->Extra != null) {
                        continue;
                    }
                    $arrayValues [] = $this->processValueType($value, $type, $isNull);
                }
            }
            if ($countEmpty == count($data) || count($arrayValues) == 0) {
                continue;
            }
            if ($editType == 0) {
                $sql = 'UPDATE `' . $msc->table . '` SET ' . implode(', ', $arrayValues) . ' WHERE ' . $where;
            } else {
                $sql = 'INSERT INTO `' . $msc->table . '` (`' . implode('`, `', $arrayFields) . '`) VALUES ('
                    . implode(', ', $arrayValues) . ')';
            }
            if ($msc->execPdo($sql)) {
                $msc->success($lang[0][$editType], $sql);
                $countInsert++;
            } else {
                $msc->error($lang[1][$editType], $sql);
            }
        }
        if ($countInsert == 0) {
            $msc->notice($lang[2][$editType]);
        }
    }

    /**
     * Преобразует значение в sql-оптимальное значение для использования в запросе (edit,add). Значение либо
     * остаётся прежним (для чисел), либо становится NULL, либо закавычивается и экранируется
     *
     * @param string Значение
     * @param string Тип поля
     * @param boolean Является ли значение NULL-пустым
     * @return string Результат
     * @package sql
     */
    private function processValueType($value, $type, $isNull)
    {
        global $pdo;
        if ($isNull) {
            return 'NULL';
        } elseif (stripos($type, 'int') > -1 && !empty($value) && is_numeric($value)) {
            return $value;
        } else {
            return $pdo->quote($value);
        }
    }
}
