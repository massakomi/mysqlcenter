<?php

declare(strict_types=1);

namespace controller;

use database\Server;

/**
 *
 */
class Search extends Base
{
    public function defaultAction(): array
    {
        global $msc;

        $array = POST('table');
        $query = POST('query');
        $queryField = POST('queryField');
        $listTables = \DatabaseTable::getTables();

        if (isAjax()) {
            if (GET('db') && !in_array(GET('db'), Server::getDatabases())) {
                ajaxError('База данных не найдена');
            }
            if (GET('table') && !in_array(GET('table'), $listTables)) {
                ajaxError('Таблица не найдена');
            }
        }

        $pageProps = [
            'query' => POST('query'),
            'queryField' => POST('queryField'),
            'search_for' => POST('search_for'),
            'replace_in' => POST('replace_in'),
            'tables' => $listTables
        ];

        // 1. Режим поиска по таблице
        if ($msc->table != null) {
            $msc->pageTitle = 'Поиск по таблице';
            $pageProps ['fields'] = \DatabaseTable::getFields(GET('table'), true);
            return $pageProps;
        }

        // 2. Режим поиска по БД
        $msc->pageTitle = 'Поиск по базе данных';
        if ($queryField && strlen($queryField) > 0) {
            $results = [];
            $founded = 0;
            $foundedTotal = 0;
            foreach ($listTables as $table) {
                $fields = \DatabaseTable::getFields($table, true);
                $founds = [];
                foreach ($fields as $field) {
                    if (stristr($field, $queryField)) {
                    //if (preg_match('~[a-z][A-Z]~', $field,)) {
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
            $msc->pageTitle = "Результаты поиска по полям (найдено таблиц $founded, полей $foundedTotal)";
            $pageProps = $pageProps + compact('results', 'founded', 'foundedTotal');
        } elseif ($query && strlen($query) > 0) {
            $msc->pageTitle = "Поиск: '$query'";
            if ($array == null || count($array) == 0) {
                $array = [$msc->table];
                $msc->pageTitle = "Поиск - таблица $msc->table";
            }

            $results = [];
            $founded = 0;
            foreach ($array as $table) {
                $fields = \DatabaseTable::getFields($table, true);
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

            $msc->pageTitle = "Результаты поиска (найдено <b>$founded</b>)";
            $pageProps = $pageProps + compact('results', 'founded');
        }

        return $pageProps;
    }
}
