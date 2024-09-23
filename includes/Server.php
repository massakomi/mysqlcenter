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
        static $array;
        if (!isset($array)) {
            global $connection;
            $db_list = mysqli_query($connection, 'SHOW DATABASES');
            $array = [];
            while ($row = mysqli_fetch_object($db_list)) {
                $array [] = $row->Database;
            }
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

    /**
     * @return bool
     */
    public static function userAdd()
    {
        global $msc;

        $username  = $_POST['databaseuser'];
        $database  = $_POST['database'];
        $userpass  = $_POST['userpass'];

        /*
        $result = $msc->query('DROP USER massakomi');
        var_dump($result);
        */

        // Проверяем, может уже есть такой пользователь
        $sql = 'SELECT * FROM mysql.user WHERE User="'.$username.'"';
        $result = $msc->getOne($sql);
        if ($result) {
            $msc->addMessage('Пользователь с именем "'.$username.'" уже существует', '', MS_MSG_NOTICE);
            return false;
        }


        // Сначала добавляем пользователя
        $sql = 'CREATE USER `'.$username.'` IDENTIFIED BY "'.$userpass.'"';
        $result = $msc->query($sql);
        if ($result) {
            $msc->addMessage('Пользователь "'.$username.'" добавлен', $sql, MS_MSG_SUCCESS);
        } else {
            $msc->addMessage('Ошибка добавления пользователя "'.$username.'"', $sql, MS_MSG_FAULT);
            return false;
        }

        // Теперь добавляем базу данных
        $sql = 'CREATE DATABASE `'.$database.'`';
        $result = $msc->query($sql);
        if ($result) {
            $msc->addMessage('База данных "'.$database.'" создана', $sql, MS_MSG_SUCCESS);
        } else {
            $msc->addMessage('Ошибка создания базы данных "'.$database.'"', $sql, MS_MSG_FAULT);
            return false;
        }

        // Теперь наделяем привелегиями пользователя на эту базу
        $sql = 'GRANT ALL ON `'.$database.'`.* TO `'.$username.'`';
        $result = $msc->query($sql);
        if ($result) {
            $msc->addMessage('Права на базу "'.$database.'" отданы пользоватлю "'.$username.'"', $sql, MS_MSG_SUCCESS);
        } else {
            $msc->addMessage('Ошибка наделения прав на базу "'.$database.'"', $sql, MS_MSG_FAULT);
            return false;
        }
        return true;
    }
}

