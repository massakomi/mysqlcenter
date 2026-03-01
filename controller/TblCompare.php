<?php

declare(strict_types=1);

namespace controller;

use database\Table;
use dto\FieldInfo;

class TblCompare extends Base
{
    /**
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function defaultAction(): array
    {
        global $msc;
        $msc->pageTitle = 'Сравнение таблиц';

        // Проверка
        $tables = POST('table') ?: [GET('table')];
        if (count($tables) < 1) {
            $msc->error('Вы не выбрали таблиц для сравнения');

            return [];
        }
        // Получение массив баз данных
        if (count($_POST)) {
            if (isset($_POST['databases'])) {
                $databases = is_array($_POST['databases']) ? $_POST['databases'] : explode(',', $_POST['databases']);
            } else {
                $databases = [$_POST['database'], $msc->db];
            }
        } else {
            $databases = [GET('db'), GET('db2')];
        }

        $pageProps = [
            'databases' => $databases,
            'tables' => [],
        ];
        foreach ($tables as $table) {
            $fields = Table::getFields($table);
            $pk = $this->getPrimaryKeys($fields);
            [$data1, $data2] = $this->selectDataFromDatabase($databases, $table, $pk);
            foreach (array_keys($data1[0]) + array_keys($data2[0]) as $field) {
                if (!isset($fields[$field])) {
                    $fields[$field] = null;
                }
            }
            $tableData = compact('fields', 'pk', 'data1', 'data2');
            $pageProps['tables'][$table] = $tableData;
        }

        return $pageProps;
    }

    /**
     * @param FieldInfo[] $fields
     *
     * @return array<string>
     */
    private function getPrimaryKeys(array $fields): array
    {
        $pk = [];
        foreach ($fields as $v) {
            if (strchr($v->Key, 'PRI')) {
                $pk[] = $v->Field;
            }
        }

        return $pk;
    }

    /**
     * @param array<string> $databases
     * @param array<string> $pk
     *
     * @return array<int, list<mixed>>
     *
     * @throws \Exception
     */
    private function selectDataFromDatabase(array $databases, string $table, array $pk): array
    {
        global $msc;
        $msc->selectDb($databases[0]);
        // Порядок
        $orderBy = null;
        if (count($pk) > 0) {
            $orderBy = ' ORDER BY '.implode(',', $pk); // . ' DESC';
        }
        // Первая  БД
        $sql = "SELECT * FROM $table";
        $result = $msc->fetchPdo($sql.$orderBy);
        $data1 = [];
        if (!$result) {
            $msc->error("Таблица $table не найдена в базе $databases[0]");

            return [];
        }
        while ($row = $result->fetch()) {
            $data1[] = $row;
        }

        // Вторая БД
        $data2 = [];
        $msc->selectDb($databases[1]);
        $sql = "SELECT * FROM $table";
        $result = $msc->fetchPdo($sql.$orderBy);
        while ($row = $result->fetch()) {
            $data2[] = $row;
        }

        return [$data1, $data2];
    }
}
