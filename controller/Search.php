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

        $query = POST('query');
        $queryField = POST('queryField');
        $listTables = Table::getTables();

        $pageProps = [
            'query' => POST('query'),
            'queryField' => POST('queryField'),
            'search_for' => POST('search_for'),
            'replace_in' => POST('replace_in'),
            'tables' => $listTables
        ];

        // по таблице
        if ($msc->table != null) {
            $msc->pageTitle = 'Поиск по таблице ' . $msc->table;
            $pageProps ['table'] = $msc->table;
            $pageProps ['fields'] = Table::getFieldNames(GET('table'));
            return $pageProps;
        }

        // по полям БД
        $msc->pageTitle = 'Поиск по базе данных';
        if ($queryField && strlen($queryField) > 0) {
            $props = $this->searchFieldInDatabase($listTables, $queryField);
            return $pageProps + $props;
        }

        // по БД
        if ($query && strlen($query) > 0) {
            $props = $this->searchInDatabase($query);
            return $pageProps + $props;
        }

        return $pageProps;
    }

    /**
     * @param string $query
     * @return array<string, int<0, max>|list<array<string, mixed>>>
     * @throws \Exception
     */
    private function searchInDatabase(string $query): array
    {
        global $msc;
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

        $msc->pageTitle = "Результаты поиска (найдено <b>$founded</b>)";
        return compact('results', 'founded');
    }

    /**
     * @param array<string> $listTables
     * @param string $queryField
     * @return array<string, int<0, max>|list<array<string, array<string, string>|string>>>
     */
    private function searchFieldInDatabase(array $listTables, string $queryField): array
    {
        global $msc;
        $results = [];
        $founded = 0;
        $foundedTotal = 0;
        foreach ($listTables as $table) {
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
        $msc->pageTitle = "Результаты поиска по полям (найдено таблиц $founded, полей $foundedTotal)";
        return compact('results', 'founded', 'foundedTotal');
    }
}
