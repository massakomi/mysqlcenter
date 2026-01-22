<?php

declare(strict_types=1);

namespace controller;

use database\Table;

class Search extends Base
{
    /**
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function defaultAction(): array
    {
        global $msc;

        // Форма поиска по таблице
        if ($msc->table != null) {
            $msc->pageTitle = 'Поиск по таблице '.$msc->table;

            return [
                'tables' => Table::getTables(),
                'table' => $msc->table,
                'fields' => Table::getFieldNames(GET('table')),
            ];
        }

        return [
            'tables' => Table::getTables(),
        ];
    }

    /**
     * @return array<string, mixed>
     *
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
            $fields = Table::getFields($table);
            $whereCondition = Table::whereCondition($fields, $query);
            $sql = "SELECT COUNT(*) as c FROM $table $whereCondition";
            $result = $msc->fetchPdo($sql);
            if (!$result) {
                continue;
            }
            // найдено что-то
            if ($row = $result->fetchObject()) {
                if ($row->c > 0) {
                    ++$founded;
                    $results[] = [
                        'table' => $table,
                        'rows' => [
                            'href' => "/?s=tbl_data&db=$msc->db&table=$table&query=$query",
                            'text' => $row->c,
                        ],
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
     *
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
            $fields = Table::getFields($table);
            $founds = [];
            foreach ($fields as $field) {
                if ($this->matchesPattern($field->Field, $queryField)) {
                    $founds[] = $field;
                    ++$foundedTotal;
                }
            }
            // найдено что-то
            if (count($founds) > 0) {
                ++$founded;
                $results[] = [
                    'table' => ['href' => "/?s=tbl_data&db=$msc->db&table=$table", 'text' => $table],
                    'fields' => $founds,
                ];
            }
        }
        $tables = Table::getTables();
        $msc->pageTitle = "Результаты поиска по полям (найдено таблиц $founded, полей $foundedTotal)";

        return compact('results', 'founded', 'foundedTotal', 'tables');
    }

    /**
     * Check if field name matches the search pattern.
     *
     * @param string $fieldName The field name to check
     * @param string $pattern The search pattern which might contain wildcards
     */
    private function matchesPattern(string $fieldName, string $pattern): bool
    {
        // If pattern contains regex special chars or wildcards, treat as regex
        if (preg_match('~[*?\[\]]~', $pattern)) {
            // Escape regex special chars except for [] and -
            // We do this by escaping all except these chars
            $escaped = '';
            $len = strlen($pattern);
            for ($i = 0; $i < $len; ++$i) {
                $char = $pattern[$i];
                if (in_array($char, ['*', '?'])) {
                    // leave for replacement later
                    $escaped .= $char;
                } elseif ($char === '[' || $char === ']' || $char === '-') {
                    // leave as is
                    $escaped .= $char;
                } else {
                    // escape regex special chars
                    $escaped .= preg_quote($char, '/');
                }
            }

            // Replace wildcards with regex equivalents
            $regex = '/'.str_replace(
                ['*', '?'],
                ['.*', '.'],
                $escaped
            ).'/';

            return (bool) preg_match($regex, $fieldName);
        }

        // Fall back to normal string search for patterns without wildcards
        return stristr($fieldName, $pattern) !== false;
    }
}
