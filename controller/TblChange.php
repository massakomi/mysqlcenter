<?php

namespace controller;

/**
 *
 */
class TblChange
{

    public function __construct()
    {
        global $msc, $pagel;
        if ($msc->table == '') {
            $msc->notice('Не указана таблица в запросе');
            return false;
        }

        $tableData = [];

        /**
         * Вставка/изменение рядов
         */
        if (GET('row') == '' && POST('row') == '') {
            $msc->pageTitle = 'Добавить строки в таблицу';
            $fields = \DatabaseTable::getFields($msc->table);
            $isAdd = true;
        } else {

            $msc->pageTitle = 'Редактировать данные';

            // если в запросе есть ряд (и таблица), то редактируем этот ряд
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

            // создания таблицы для данных
            $fields = \DatabaseTable::getFields($msc->table);
            if ($whereCondition != null) {
                $tableData = $msc->getData('SELECT * FROM '.$msc->table.' WHERE '.$whereCondition);
                if (!$tableData) {
                    $msc->notice('Ничего не выбрано');
                    return;
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
            'isAdd' => $isAdd
        ];
        if (isajax()) {
            return $pageProps;
        }

        $pagel->template($pageProps);
    }
}
