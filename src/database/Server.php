<?php

declare(strict_types=1);

namespace database;

use service\Validate;

/**
 * Класс Server - сервер, где расположены базы данных.
 */
class Server
{
    private Validate $validate;

    public function __construct()
    {
        $this->validate = new Validate();
    }

    /**
     * Возвращает массив баз данных.
     *
     * @return array<string>
     */
    public static function getDatabases(): array
    {
        static $array;
        if (!isset($array)) {
            global $msc;
            $array = $msc->driver ? $msc->driver->getDatabaseNames() : [];
        }

        return $array;
    }

    /**
     * @return array<string>
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
     * Определение версии сервера в виде числа и строки.
     *
     * @throws \Exception
     */
    public static function getServerVersion(): string
    {
        global $msc;
        $result = $msc->fetchPdo('SELECT VERSION() AS version');
        if (!$result) {
            return 'Unknown';
        }
        $row   = $result->fetch();
        $match = explode(' ', $row['version']);
        $match = array_slice($match, 0, 2);

        return implode(' ', $match);
    }

    /**
     * Возвращает массив кодировок сервера.
     *
     * @param bool $extended Возвратить полную инфорамцию в виде массива объектов, либо только массив кодировок
     *
     * @return array<string>
     */
    public static function getCharsetArray(bool $extended = false): array
    {
        global $msc;
        $charsetList = [];
        $data = $msc->driver->getCharsets();
        foreach ($data as $row) {
            $charsetList[$row['Charset']] = $extended ? $row : $row['Charset'];
        }
        ksort($charsetList);

        return $charsetList;
    }

    /**
     * Удаляет / создаёт БД.
     *
     * @param string $type DROP|CREATE
     * @param string|null $user Для постгрес роль
     * @param string|null $option Для постгрес template
     *
     * @throws \Exception
     */
    public function databaseAction(string $db, string $type, ?string $user = '', ?string $option = ''): bool
    {
        global $msc;
        if (!$this->validate->queryCheck($db)) {
            return false;
        }
        switch ($type) {
            case 'DROP':
                $sql = "DROP DATABASE `$db`;";
                if ($msc->driverName == 'pgsql' && $db === $msc->db) {
                    // В постгрес нельзя удалить текущую бд, поэтому нужно поменять на другую
                    $dbs = Server::getDatabases();
                    $dbs = array_filter($dbs, fn ($value) => $value !== $db);
                    if (!count($dbs)) {
                        return $msc->error('Невозможно удалить единственную БД');
                    }
                    $msc->selectDb($dbs[0]);
                }
                $text = 'удалена';
                break;
            case 'CREATE':
                $sql = "CREATE DATABASE `$db`";
                if ($msc->driverName == 'pgsql') {
                    if ($option) {
                        $sql .= " TEMPLATE `$option`;";
                    }
                    if ($user) {
                        $sql .= " OWNER `$user`;";
                    }
                }
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
     * Удаляет / очищает все таблицы БД.
     *
     * @param string $db База данных
     * @param bool $delete Если true - удалить таблицы, иначе очистить
     *
     * @return bool true только если ошибок нет, false - если хотя бы одна таблица не обработана
     */
    public function databaseTruncate(string $db, bool $delete = false): bool
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
                    ++$errors;
                }
            } elseif ($dbt->tableAction($db, $t, 'TRUNCATE')) {
                ++$errors;
            }
        }

        return $errors == 0;
    }

    /**
     * Копирует / переименовывает БД.
     *
     * @param string $dbFrom БД-источник
     * @param string $dbTo БД, куда копируется/перемещается
     * @param bool $isMove Если true, то перемещает, иначе копирует
     * @param bool $struct Если true, копирует структуру (CREATE TABLE...), иначе нет
     * @param bool $data Если true, копирует данные, иначе нет
     *
     * @throws \Exception
     */
    public function databaseCopy(string $dbFrom, string $dbTo, bool $isMove = false, bool $struct = true, bool $data = true): bool
    {
        global $msc;
        if ($msc->driverName == 'pgsql') {
            if ($this->databaseAction($dbTo, 'CREATE', '', $dbFrom)) {
                return true;
            }

            return false;
        }
        if ($this->databaseAction($dbTo, 'CREATE')) {
            $dbt = new Table();
            // скопировать все таблицы туда и удалить из старой БД
            $a = Table::getTables($dbFrom);
            foreach ($a as $table) {
                $dbt->copyTable($dbFrom, $table, $struct, $data, $table, $dbTo);
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
     * Изменяет кодировку или сравнение БД.
     *
     * @param string $db БД
     * @param string $charset Кодировка
     * @param bool $isCharset Если true, то меняется кодировка, иначе сравнение
     *
     * @throws \Exception
     */
    public function databaseAlterCharset(string $db, string $charset, bool $isCharset = true): bool
    {
        global $msc;
        if (!$this->validate->queryCheck($db, 'table', $charset)) {
            return false;
        }
        if (!$isCharset) {
            $sql = "ALTER DATABASE $db COLLATE `$charset`";
        } else {
            $sql = "ALTER DATABASE $db CHARACTER SET `$charset`";
        }

        return $msc->execPdo($sql);
    }
}
