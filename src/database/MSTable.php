<?php

declare(strict_types=1);

namespace database;

/**
 * Класс для работы с собственной БД приложения
 */
class MSTable
{
    public $data;

    /**
     * Статистика просмотров баз данных
     * @return void
     * @throws Exception
     */
    public static function dbViewStat(): void
    {
        global $msc;
        if (!$msc->connected()) {
            return;
        }
        $dbs = Server::getDatabases();
        if (!in_array('mysqlcenter', $dbs) || empty($msc->db)) {
            return;
        }
        $msc->disableLog();
        $d = date('Y-m-d H:i:s');
        $a = $msc->getData(
            'SELECT * FROM mysqlcenter.db_info WHERE db_name="' . $msc->db . '"',
            \PDO::FETCH_OBJ
        );
        if (count($a) == 0) {
            $msc->execPdo('REPLACE INTO mysqlcenter.db_info VALUES("' . $msc->db . '", 1, 1, "' . $d . '")');
        } else {
            $msc->execPdo('UPDATE mysqlcenter.db_info SET views=views+1, last_view="' . $d .
                '" WHERE db_name="' . $msc->db . '"');
        }

        // Статистика просмотров таблиц
        if ($msc->table != '') {
            $a = $msc->getData('SELECT * FROM mysqlcenter.table_info WHERE db_name="' . $msc->db .
                '" AND table_name="' . $msc->table . '"', \PDO::FETCH_OBJ);
            if (count($a) == 0) {
                $values = $msc->db . '", "' . $msc->table . '", 1, 1, "' . $d;
                $msc->execPdo('REPLACE INTO mysqlcenter.table_info VALUES("' . $values . '")');
            } else {
                $msc->execPdo('UPDATE mysqlcenter.table_info SET views=views+1, last_view="' . $d .
                    '" WHERE db_name="' . $msc->db . '" AND table_name="' . $msc->table . '"');
            }
        }
        $msc->enableLog();
    }

    /**
     * Возвращает все переменные сета
     */
    public static function getSetInfo($idSet)
    {
        if ($idSet == null) {
            return [];
        }
        global $msc;
        $result = $msc->fetchPdo('SELECT * FROM mysqlcenter.export_table WHERE id_set=' . $idSet);
        if (!$result) {
            return [];
        }
        $a = [];
        while ($o = $result->fetchObject()) {
            $a [$o->table_name] = $o;
        }
        return $a;
    }

    /**
     * Возвращает массив сетов
     */
    public static function getSetsArray()
    {
        global $msc;
        return $msc->getData('SELECT id, name FROM mysqlcenter.export_set', \PDO::FETCH_KEY_PAIR);
    }

    /**
     * Возвращает массив сетов
     */
    public static function getHiddensArray()
    {
        global $msc;
        return $msc->getData('SELECT db_name FROM mysqlcenter.db_info WHERE visible=0', \PDO::FETCH_COLUMN);
    }

    /**
     * Добавляет новый сет
     */
    public static function insertSet($name)
    {
        global $msc, $pdo;
        if ($msc->execPdo('INSERT INTO mysqlcenter.export_set (`name`) VALUES ("' . $name . '")')) {
            return $pdo->lastInsertId();
        } else {
            return false;
        }
    }

    /**
     * Вставляет новую переменю сета
     */
    public static function insertOption($id_set, $table_name, $struct, $data, $where_sql, $pk_top)
    {
        global $msc, $pdo;
        if ($struct + $data == 0) {
            return false;
        }
        $sql = "INSERT INTO mysqlcenter.export_table(id_set, table_name, struct, data, where_sql, pk_top) VALUES
            ('$id_set', '$table_name', '$struct', '$data', '$where_sql', '$pk_top')";
        if ($msc->execPdo($sql)) {
            return $pdo->lastInsertId();
        } else {
            return false;
        }
    }
}
