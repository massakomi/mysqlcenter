<?php

declare(strict_types=1);

namespace service;

use database\Table;

/**
 * Класс для сохранения, выборки и хранения массива популярных таблиц
 */
class PopularTables
{
    /**
     * @param string $db
     * @return array
     */
    public static function getPopularTables(string $db): array
    {
        if (!file_exists(MS_POPULAR_TABLES_FILE) || !$db) {
            return [];
        }
        $json = file_get_contents(MS_POPULAR_TABLES_FILE);
        $json = json_decode($json, true);
        if (!array_key_exists($db, $json)) {
            $json[$db] = [];
        }
        if (GET('resetPopular')) {
            $json[$db] = [];
            file_put_contents(MS_POPULAR_TABLES_FILE, json_encode($json));
        }
        ksort($json[$db]);
        foreach ($json[$db] as $table => $values) {
            if (!is_array($values)) {
                $json[$db][$table] = ['count' => $values];
            }
        }
        return $json;
    }

    /**
     * Популярные таблицы для указанной БД
     * @param string $db
     * @return array
     */
    public static function forDb(string $db): array
    {
        $tables = self::getPopularTables($db);
        if ($db && array_key_exists($db, $tables)) {
            return $tables[$db];
        } else {
            return [];
        }
    }

    /**
     * Сохраняет хит о просмотре таблицы в БД
     * @param string $db
     * @param string $table
     * @return void
     */
    public static function save(string $db, string $table): void
    {
        if (!$db || !$table) {
            return;
        }
        $tables = self::getPopularTables($db);
        if (!array_key_exists($db, $tables)) {
            $tables[$db] = [];
        }
        if (array_key_exists($table, $tables[$db])) {
            $tables[$db] [$table]['count']++;
        } else {
            $tables[$db] [$table]['count'] = 1;
        }
        $tables[$db] [$table]['time'] = time();
        if (date('i') % 10 == 0) {
            $tablesAll = Table::getTables();
            $exists = array_intersect(array_keys($tables[$db]), $tablesAll);
            $notExists = array_diff(array_keys($tables[$db]), $exists);
            if (count($notExists) > 0) {
                foreach ($notExists as $table) {
                    unset($tables[$db][$table]);
                }
            }
        }
        file_put_contents(MS_POPULAR_TABLES_FILE, json_encode($tables));
    }
}
