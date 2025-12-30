<?php

declare(strict_types=1);

namespace controller;

/**
 *
 */
class TblList extends Base
{
    /**
     * @throws \Exception
     */
    public function defaultAction(): array
    {
        global $msc;

        $tables = \DatabaseTable::getCashedTablesArray();
        if (count($tables) == 0) {
            $msc->error('В базе данных нет таблиц');
        }


        // Исследование структуры
        if (GET('action') == 'structure' || GET('mode') == 'structure') {
            $msc->pageTitle = 'Структура таблиц базы данных "' . $msc->db . '" ';
            foreach ($tables as $table) {
                $table->fields = \DatabaseTable::getFields($table->Name);
                $table->data = $msc->getData('SELECT * FROM ' . $table->Name . ' LIMIT 3');
            }
            $pageProps = [
                'tables' => $tables
            ];

        // Полная таблица
        } else {
            $msc->pageTitle = 'Список таблиц базы данных "' . $msc->db . '" ';
            $action = POST('act');
            foreach ($tables as $key => $o) {
                if (array_key_exists('drop', $_GET)) {
                    echo 'DROP TABLE `' . $o->Name . '`;<br />';
                }

                if (
                    $action == 'analyze' || $action == 'check' || $action == 'flush' || $action == 'repair'
                    || $action == 'optimize'
                ) {
                    $sql = strtoupper($action) . ' TABLE `' . $o->Name . '`';
                    if ($msc->execPdo($sql)) {
                        $msc->success('Запрос выполнен', $sql);
                    } else {
                        $msc->error('Ошибка запроса', $sql);
                    }
                }
            }

            $pageProps = [
                'showtableupdated' => config('showtableupdated') == '1',
                'full' => GET('action') == 'full',
                'tables' => $tables,
                'dirImage' => MS_DIR_IMG,
                'db' => $msc->db
            ];
        }
        return $pageProps;
    }
}
