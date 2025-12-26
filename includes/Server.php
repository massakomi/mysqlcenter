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
            global $msc;
            $array = $msc->getData('SHOW DATABASES', PDO::FETCH_COLUMN);
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
     * Определение версии сервера в виде числа и строки
     *
     * @package sql
     * @return array Числовое и строковое значение версии
     */
    public static function getServerVersion() {
        global $msc;
        $result = $msc->fetchPdo('SELECT VERSION() AS version');
        if (!$result) {
            return ['-', '-'];
        }
        $row   = $result->fetch();
        $match = explode('.', $row['version']);
        $vi = (int)sprintf('%d%02d%02d', $match[0], $match[1], intval($match[2]));
        $vs = $row['version'];
        return [$vi, $vs];
    }

    /**
     * Возвращает массив кодировок сервера.
     *
     * @package sql
     * @param boolean Возвратить полную инфорамцию в виде массива объектов, либо только массив кодировок
     * @return array
     */
    public static function getCharsetArray($extended=false) {
        global $msc;
        $charsetList = array();
        $res = $msc->fetchPdo('SHOW CHARACTER SET');
        foreach ($res as $row) {
            $charsetList [$row['Charset']]= $extended ? $row : $row['Charset'];
        }
        ksort($charsetList);
        return $charsetList;
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


        /*$result = $msc->query('DROP USER ""');
        var_dump($result);
        exit;*/

        // Проверяем, может уже есть такой пользователь
        $sql = 'SELECT * FROM mysql.user WHERE User="'.$username.'"';
        $result = $msc->fetchPdo($sql);
        if ($result) {
            $msc->addMessage('Пользователь с именем "'.$username.'" уже существует', '', MS_MSG_NOTICE);
            return false;
        }


        // Сначала добавляем пользователя
        $sql = 'CREATE USER `'.$username.'` IDENTIFIED BY "'.$userpass.'"';
        $result = $msc->execPdo($sql);
        if ($result) {
            $msc->addMessage('Пользователь "'.$username.'" добавлен', $sql, MS_MSG_SUCCESS);
        } else {
            $msc->addMessage('Ошибка добавления пользователя "'.$username.'"', $sql, MS_MSG_FAULT);
            return false;
        }

        // Теперь добавляем базу данных
        $sql = 'CREATE DATABASE `'.$database.'`';
        $result = $msc->execPdo($sql);
        if ($result) {
            $msc->addMessage('База данных "'.$database.'" создана', $sql, MS_MSG_SUCCESS);
        } else {
            $msc->addMessage('Ошибка создания базы данных "'.$database.'"', $sql, MS_MSG_FAULT);
            return false;
        }

        // Теперь наделяем привелегиями пользователя на эту базу
        $sql = 'GRANT ALL ON `'.$database.'`.* TO `'.$username.'`';
        $result = $msc->execPdo($sql);
        if ($result) {
            $msc->addMessage('Права на базу "'.$database.'" отданы пользоватлю "'.$username.'"', $sql, MS_MSG_SUCCESS);
        } else {
            $msc->addMessage('Ошибка наделения прав на базу "'.$database.'"', $sql, MS_MSG_FAULT);
            return false;
        }
        return true;
    }
}

