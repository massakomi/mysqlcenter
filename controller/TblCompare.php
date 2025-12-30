<?php
declare(strict_types=1);

namespace controller;

/**
 *
 */
class TblCompare extends Base
{
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
            'tables' => []
        ];
        foreach ($tables as $table) {
            $fields = \DatabaseTable::getFields($table);
            $pk = $this->getPrimaryKeys($fields);
            [$data1, $data2] = $this->selectDataFromDatabase($databases, $table, $pk);
            $tableData = compact('fields', 'pk', 'data1', 'data2');
            $pageProps ['tables'][$table] = $tableData;
        }

        return $pageProps;
    }

    /**
     * @param $fields
     * @return array
     */
    private function getPrimaryKeys($fields): array
    {
        $pk = [];
        foreach ($fields as $v) {
            if (strchr($v->Key, 'PRI')) {
                $pk [] = $v->Field;
            }
        }
        return $pk;
    }

    /**
     * @param $databases
     * @param $table
     * @param $pk
     * @return array[]
     * @throws Exception
     */
    private function selectDataFromDatabase($databases, $table, $pk): array
    {
        global $msc;
        $msc->selectDb($databases[0]);
        // Порядок
        $orderBy = null;
        if (count($pk) > 0) {
            $orderBy = ' ORDER BY ' . implode(',', $pk); // . ' DESC';
        }
        // Первая  БД
        $sql = "SELECT * FROM $databases[0].$table";
        $result = $msc->fetchPdo($sql . $orderBy);
        $data1 = [];
        if (!$result) {
            $msc->error("Таблица $table не найдена в базе $databases[0]");
            return [];
        }
        while ($row = $result->fetch(\PDO::FETCH_OBJ)) {
            $data1 [] = $row;
        }

        // Вторая БД
        $data2 = [];
        $msc->selectDb($databases[1]);
        $sql = "SELECT * FROM $table";
        $result = $msc->fetchPdo($sql . $orderBy);
        while ($row = $result->fetch(\PDO::FETCH_OBJ)) {
            $data2 [] = $row;
        }

        return [$data1, $data2];
    }
}
