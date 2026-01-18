<?php

declare(strict_types=1);

namespace controller;

use database\Table;
use service\UrlMaker;

/**
 * Вставка/изменение рядов.
 */
class TblChange extends Base
{
    /**
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function defaultAction(): array
    {
        global $msc;
        if ($msc->table == '') {
            $msc->error('Не указана таблица в запросе');

            return [];
        }

        if (in_array(POST('action'), ['rowsEdit', 'rowsAdd'])) {
            $this->processRowsEdit($_POST['action'] == 'rowsEdit' ? 0 : 1);
        }

        $tableData = [];
        $fields = Table::getFields($msc->table);

        $isAdd = (GET('row') == '' && POST('cond') == '');
        if ($isAdd) {
            $msc->pageTitle = 'Добавить строки в таблицу';
        } else {
            $msc->pageTitle = 'Редактировать данные';
            $whereCondition = $this->whereCondition();
            if ($whereCondition != null) {
                $sql = 'SELECT * FROM '.$msc->table.' WHERE '.$whereCondition;
                $tableData = $msc->getData($sql);
                if (!$tableData) {
                    $msc->error('Ничего не выбрано', $sql);
                }
            }
        }


        $pageProps = [
            'table' => $msc->table,
            'db' => $msc->db,
            'fields' => $fields,
            'tableData' => $tableData,
            'msRowsInsert' => MS_ROWS_INSERT,
            'dirImage' => MS_DIR_IMG,
            'isAdd' => $isAdd,
        ];
        if (POST('redirect')) {
            $pageProps['redirect'] = UrlMaker::make('s', POST('redirect'));
        }

        return $pageProps;
    }

    /**
     * Если в запросе есть ряд (и таблица), то редактируем этот ряд.
     */
    private function whereCondition(): ?string
    {
        $whereCondition = null;
        $array = POST('cond');
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
     * Общая обработка для редактирования/добавления ряда.
     *
     * @param int $editType Тип редактирования: 0-update, 1-insert
     */
    private function processRowsEdit(int $editType): void
    {
        global $msc;
        if (POST('action') == 'rowsAdd') {
            $editType = 1;
        }
        $countInsert = 0;
        $fields = array_values(Table::getFields($msc->table));
        $arrayFields = [];
        if ($editType == 1) {
            foreach ($fields as $v) {
                if (POST('action') == 'rowsAdd' && $v->Extra != null) {
                    continue;
                }
                $arrayFields[] = $v->Field;
            }
        }
        $lang = [
            ['Данные обновлены', 'Данные добавлены'],
            ['Ошибка при обновлении ряда', 'Ошибка при добавлении ряда'],
            ['Ничего не обновилось', 'Ничего не добавилось'],
        ];
        $rows = POST('row');
        $_POST['cond'] = POST('cond');
        foreach ($rows as $numRow => $data) {
            $where = $cValue = '';
            if ($editType == 0) {
                $where = urldecode($_POST['cond'][$numRow]);
                $cValue = $msc->fetchPdo('SELECT * FROM `'.$msc->table.'` WHERE '.$where)->fetchObject();
            }
            $arrayValues = [];
            $countEmpty = 0;
            foreach ($data as $key => $value) {
                $default = $fields[$key]->Default;
                if ($default == $value) {
                    ++$countEmpty;
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
                    if ($type == 'boolean') {
                        $value = $value === 'false' ? '0' : '1';
                    }
                    if ($value != $cValue->$field) {
                        $arrayValues[] = '`'.$field.'`='.$this->processValueType($value, $type, $isNull);
                    }
                } else {
                    if (POST('action') == 'rowsAdd' && $fields[$key]->Extra != null) {
                        continue;
                    }
                    $arrayValues[] = $this->processValueType($value, $type, $isNull);
                }
            }

            if ($countEmpty == count($data) || count($arrayValues) == 0) {
                continue;
            }
            if ($editType == 0) {
                $sql = 'UPDATE `'.$msc->table.'` SET '.implode(', ', $arrayValues).' WHERE '.$where;
            } else {
                $sql = 'INSERT INTO `'.$msc->table.'` (`'.implode('`, `', $arrayFields).'`) VALUES ('
                    .implode(', ', $arrayValues).')';
            }
            if ($msc->execPdo($sql)) {
                $msc->success($lang[0][$editType], $sql);
                ++$countInsert;
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
     * остаётся прежним (для чисел), либо становится NULL, либо закавычивается и экранируется.
     *
     * @param string|null $value Значение
     * @param string $type Тип поля
     * @param bool $isNull Является ли значение NULL-пустым
     *
     * @return float|int|string Результат
     */
    private function processValueType(?string $value, string $type, bool $isNull): float|int|string
    {
        global $pdo;
        if (is_null($value) && $isNull) {
            return 'NULL';
        } elseif (stripos($type, 'int') > -1 && !empty($value) && is_numeric($value)) {
            return $value;
        } else {
            return $pdo->quote($value);
        }
    }
}
