<?php

declare(strict_types=1);

namespace controller;

use database\Table;

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

        $tables = Table::getCashedTablesArray();
        if (count($tables) == 0) {
            $msc->error('В базе данных нет таблиц');
        }
        $msc->pageTitle = 'Список таблиц базы данных "' . $msc->db . '" ';
        return [
            'showtableupdated' => config('showtableupdated') == '1',
            'full' => GET('mode') == 'full',
            'tables' => $tables,
            'dirImage' => MS_DIR_IMG,
            'db' => $msc->db
        ];
    }

    /**
     *
     */
    public function structureAction(): array
    {
        global $msc;
        $tables = Table::getCashedTablesArray();
        $msc->pageTitle = 'Структура таблиц базы данных "' . $msc->db . '" ';
        foreach ($tables as $table) {
            $table->fields = Table::getFields($table->Name);
            $table->data = $msc->getData('SELECT * FROM ' . $table->Name . ' LIMIT 3');
        }
        return [
            'tables' => $tables
        ];
    }
}
