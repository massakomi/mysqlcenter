<?php

declare(strict_types=1);

namespace controller;

use database\Server;
use database\Table;

/**
 *
 */
class Search extends Base
{
    /**
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function defaultAction(): array
    {
        global $msc;

        // Форма поиска по таблице
        if ($msc->table != null) {
            $msc->pageTitle = 'Поиск по таблице ' . $msc->table;
            return [
                'table' => $msc->table,
                'fields' => Table::getFieldNames(GET('table'))
            ];
        }

        return [
            'tables' => Table::getTables()
        ];
    }

    /**
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function searchDbAction(): array
    {
        global $msc;
        $query = POST('query');
        $array = POST('table');
        $msc->pageTitle = "Поиск: '$query'";
        if ($array == null || count($array) == 0) {
            $array = [$msc->table];
            $msc->pageTitle = "Поиск - таблица $msc->table";
        }

        $results = [];
        $founded = 0;
        foreach ($array as $table) {
            $fields = Table::getFieldNames($table);
            $whereCondition = " WHERE " . implode(' LIKE "%' . $query . '%" OR ', $fields) . ' LIKE "%'
                . $query . '%"';
            $sql = "SELECT COUNT(*) as c FROM $table $whereCondition";
            $result = $msc->fetchPdo($sql);
            if (!$result) {
                continue;
            }
            // найдено что-то
            if ($row = $result->fetchObject()) {
                if ($row->c > 0) {
                    $founded++;
                    $results [] = [
                        'table' => $table,
                        'rows' => [
                            'href' => "/?s=tbl_data&db=$msc->db&table=$table&query=$query",
                            'text' => $row->c
                        ]
                    ];
                }
            }
        }
        $tables = Table::getTables();
        $msc->pageTitle = "Результаты поиска (найдено $founded)";
        return compact('results', 'founded', 'tables');
    }

    /**
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function searchDbFieldAction(): array
    {
        global $msc;
        $queryField = POST('queryField');
        $array = POST('table');
        $results = [];
        $founded = 0;
        $foundedTotal = 0;
        foreach ($array as $table) {
            $fields = Table::getFieldNames($table);
            $founds = [];
            foreach ($fields as $field) {
                if (stristr($field, $queryField)) {
                    $founds [] = $field;
                    $foundedTotal++;
                }
            }
            // найдено что-то
            if (count($founds) > 0) {
                $founded++;
                $results [] = [
                    'table' => ['href' => "/?s=tbl_data&db=$msc->db&table=$table", 'text' => $table],
                    'fields' => implode(', ', $founds),
                ];
            }
        }
        $tables = Table::getTables();
        $msc->pageTitle = "Результаты поиска по полям (найдено таблиц $founded, полей $foundedTotal)";
        return compact('results', 'founded', 'foundedTotal', 'tables');
    }
}
