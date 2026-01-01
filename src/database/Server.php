<?php

declare(strict_types=1);

namespace database;

use service\Validate;

/**
 * Класс Server - сервер, где расположены базы данных
 */
class Server
{
    private Validate $validate;

    /**
     * @access private
     */
    public function __construct()
    {
        $this->validate = new Validate();
    }

    /**
     * Возвращает массив баз данных
     */
    public static function getDatabases()
    {
        static $array;
        if (!isset($array)) {
            global $msc;
            $array = $msc->driver->getDatabases();
        }
        return $array;
    }

    /**
     * @return array
     */
    public static function getDatabasesWithoutHidden(): array
    {
        global $msc;
        if (!$msc->connected()) {
            return [];
        }
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
    public static function getServerVersion()
    {
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
    public static function getCharsetArray($extended = false)
    {
        global $msc;
        $charsetList = [];
        $data = $msc->driver->getCharsets();
        foreach ($data as $row) {
            $charsetList [$row['Charset']] = $extended ? $row : $row['Charset'];
        }
        ksort($charsetList);
        return $charsetList;
    }


    /**
     * Удаляет / создаёт БД
     *
     * @param string База данных
     * @param string DROP|CREATE
     * @return boolean
     * @throws Exception
     */
    public function databaseAction($db, $type = 'DROP'): bool
    {
        global $msc;
        if (!$this->validate->queryCheck($db)) {
            return false;
        }
        switch ($type) {
            case 'DROP':
                $sql = "DROP DATABASE `$db`;";
                $text = 'удалена';
                break;
            case 'CREATE':
                $sql = "CREATE DATABASE `$db`;";
                $text = 'создана';
                break;
            default:
                return $msc->error('Неверный тип обработки');
        }
        if ($msc->execPdo($sql)) {
            return $msc->success("База данных $db $text", $sql);
        } else {
            return $msc->error("Ошибка работы с $db", $sql);
        }
    }

    /**
     * Удаляет / очищает все таблицы БД
     *
     * @param string База данных
     * @param boolean Если true - удалить таблицы, иначе очистить
     * @return boolean true только если ошибок нет, false - если хотя бы одна таблица не обработана
     */
    public function databaseTruncate($db, $delete = false)
    {
        global $msc;
        $dbt = new Table();
        if (!$this->validate->queryCheck($db)) {
            return false;
        }
        $a = Table::getTables($db);
        if (count($a) == 0) {
            return $msc->error('Таблиц нет');
        }
        $errors = 0;
        foreach ($a as $t) {
            if ($delete) {
                if (!$dbt->tableAction($db, $t, 'DROP')) {
                    $errors++;
                }
            } elseif ($dbt->tableAction($db, $t, 'TRUNCATE')) {
                $errors++;
            }
        }
        return ($errors == 0);
    }

    /**
     * Копирует / переименовывает БД
     *
     * @param string БД-источник
     * @param string БД, куда копируется/перемещается
     * @param boolean Если true, то перемещает, иначе копирует
     * @param boolean Если true, копирует структуру (CREATE TABLE...), иначе нет
     * @param boolean Если true, копирует данные, иначе нет
     * @return boolean
     * @throws Exception
     */
    public function databaseCopy($dbFrom, $dbTo, $isMove = false, $struct = true, $data = true): bool
    {
        if ($this->databaseAction($dbTo, 'CREATE')) {
            $dbt = new Table();
            // скопировать все таблицы туда и удалить из старой БД
            $a = Table::getTables($dbFrom);
            foreach ($a as $table) {
                $dbt->copyTable($dbFrom, $table, $struct, $data, $table, $dbTo);
                if ($isMove) {
                    //$dbt->tableAction($dbFrom, $table, 'DROP');
                }
            }
            // удалить БД
            if ($isMove) {
                return $this->databaseAction($dbFrom, 'DROP');
            }
            return true;
        }
        return false;
    }

    /**
     * Изменяет кодировку или сравнение БД
     *
     * @param string БД
     * @param string Кодировка
     * @param boolean Если true, то меняется кодировка, иначе сравнение
     * @return boolean
     * @throws Exception
     */
    public function databaseAlterCharset($db, $charset, $isCharset = true): bool
    {
        global $msc;
        if (!$this->validate->queryCheck($db, 'table', $charset)) {
            return false;
        };
        if (!$isCharset) {
            $sql = "ALTER DATABASE $db COLLATE `$charset`";
        } else {
            $sql = "ALTER DATABASE $db CHARACTER SET `$charset`";
        }
        return $msc->execPdo($sql);
    }
}
