<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Класс Server - сервер, где расположены базы данных
 */
class Server
{

    /**
     * Возвращает массив баз данных
     */
    public static function getDatabases()
    {
        global $connection;
        $db_list = mysqli_query($connection, 'SHOW DATABASES');
        $array = [];
        while ($row = mysqli_fetch_object($db_list)) {
            $array [] = $row->Database;
        }
        return $array;
    }

    /**
     * @return array
     */
    public static function getDatabasesWithoutHidden()
    {
        $dbs = self::getDatabases();
        $hidden = [];
        if (in_array('mysqlcenter', $dbs)) {
            include_once 'includes/MSTable.php';
            $hidden = MSTable::getHiddensArray();
        }
        foreach ($dbs as $key => $db) {
            if (in_array($db, $hidden)) {
                unset($dbs[$key]);
            }
        }
        return array_values($dbs);
    }
}

