<?php

/**
 * Класс для работы с собственной БД приложения
 */
class MSTable
{
    public $data;

    /**
     * Возвращает все переменные сета
     */
    public static function getSetInfo($idSet)
    {
        if ($idSet == null) {
            return [];
        }
        global $msc;
        $result = $msc->fetchPdoObject('SELECT * FROM mysqlcenter.export_table WHERE id_set=' . $idSet);
        $a = [];
        while ($o = $result->fetch()) {
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
        return $msc->getData('SELECT id, name FROM mysqlcenter.export_set', PDO::FETCH_KEY_PAIR);
    }

    /**
     * Возвращает массив сетов
     */
    public static function getHiddensArray()
    {
        global $msc;
        return $msc->getData('SELECT db_name FROM mysqlcenter.db_info WHERE visible=0', PDO::FETCH_COLUMN);
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
