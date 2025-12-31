<?php

namespace database;

use dto\FieldInfo;
use service\Validate;

/**
 * Класс, отвечающий за работу с таблицами базы данных
 */
class Table
{
    public ?string $database;
    public ?string $table;
    private Validate $validate;

    /**
     * @access private
     */
    public function __construct($db = null, $table = null)
    {
        $this->database = $db;
        $this->table = $table ? '`' . str_replace('`', '``', $table) . '`' : '';
        $this->validate = new Validate();
    }

    /**
     * Совершает действие типа $type с $table, используя если надо параметр $param
     *
     * @param string
     * @param string
     * @param string  DROP | TRUNCATE | ANALISE | OPTIMIZE | CHECK | REPAIR | FLUSH
     * @param string
     * @return boolean
     */
    public function tableAction($db, $table, $type = 'DROP', $param = null): bool
    {
        global $msc;
        if (!$this->validate->queryCheck($db, $table)) {
            return false;
        };
        if (($type == 'RENAME' || $type == 'CHARSET' || $type == 'ORDER') && $param == null) {
            return $msc->error('Не указан требуемый параметр');
        }
        switch ($type) {
            case 'DROP':
                $sql = "DROP TABLE `$table`";
                $text = 'удалена';
                break;
            case 'TRUNCATE':
                $sql = "TRUNCATE TABLE `$table`";
                $text = 'очищена';
                break;
            case 'CHECK':
                $sql = "CHECK TABLE `$table`";
                $text = 'обработана';
                break;
            case 'ANALYZE':
                $sql = "ANALYZE TABLE `$table`";
                $text = 'обработана';
                break;
            case 'REPAIR':
                $sql = "REPAIR TABLE `$table`";
                $text = 'обработана';
                break;
            case 'OPTIMIZE':
                $sql = "OPTIMIZE TABLE `$table`";
                $text = 'обработана';
                break;
            case 'FLUSH':
                $sql = "FLUSH TABLE `$table`";
                $text = 'обработана';
                break;
            case 'RENAME':
                $sql = "ALTER TABLE `$table` RENAME `$param`";
                $text = 'переименована';
                break;
            case 'CHARSET':
                $sql = "ALTER TABLE `$table` CONVERT TO CHARACTER SET `$param`";
                $text = 'изменена';
                break;
            case 'COMMENT':
                $sql = "ALTER TABLE `$table` COMMENT = '$param'";
                $text = 'изменена';
                break;
            case 'ORDER':
                $sql = "ALTER TABLE `$table` ORDER BY $param";
                $text = 'изменена';
                break;
            default:
                return $msc->error('Неверный тип обработки');
        }
        $msc->selectDb($db);
        if ($msc->execPdo($sql)) {
            $msc->fetchPdo("ANALYZE TABLE `$table`;");
            return $msc->success("Таблица $table $text", $sql);
        } else {
            $text = "Ошибка при выполнении операции с таблицей $table";
            return $msc->error($text, $sql);
        }
    }

    /**
     * Копирует таблицу $table, если надо со структурой, данными. если надо переименовывает
     *
     * @param string  текущая БД
     * @param string  таблица
     * @param boolean надо ли создавать таблицу на новом месте
     * @param boolean надо ли копировать данные
     * @param string  новое имя, если таблица переименовывается
     * @param string  База данных, куда надо копировать
     * @return boolean
     */
    public function copyTable($db, $table, $struct = true, $data = false, $newName = null, $database = null)
    {
        global $msc;
        if (!$this->validate->queryCheck($db, $table)) {
            return false;
        };
        // дамп структуры
        if ($newName == null) {
            $newName = $table . '_copy';
        }
        if ($database == null) {
            $database = $db;
        }
        $exp = new Export();
        $exp->setDatabase($db);
        $exp->setTable($table);
        $sql = $exp->exportStructure(1, false);
        $sql = preg_replace(
            '/CREATE TABLE ([a-zA-Z0-9_`\-]+)/i',
            'CREATE TABLE `' . $newName . '`',
            $sql,
            1
        );
        $msc->selectDb($database);
        if ($msc->execPdo($sql)) {
            $msc->success("Таблица $table скопирована", $sql);
        } else {
            $msc->error("Ошибка копирования $table", $sql);
            return false;
        }
        // переход в старую БД после запроса
        if ($database != $db) {
            $msc->selectDb($db);
        }
        // дамп данных
        if ($data) {
            if ($database != $db) {
                $sql = 'INSERT INTO ' . $database . '.' . $newName . ' SELECT * FROM ' . $db . '.' . $table;
            } else {
                $sql = "INSERT INTO $newName SELECT * FROM $table";
            }
            if ($msc->execPdo($sql)) {
                $msc->success('Данные скопированы', $sql);
            } else {
                $msc->error('Ошибка копирования данных', $sql);
                return false;
            }
        }
        return true;
    }

    /**
     * Возвращает массив SQL объектов-полей таблицы $table
     *
     * @param string $table таблица
     * @param bool $onlyNames возвратить только массив имён полей
     * @return FieldInfo[] Массив полей
     */
    public static function getFields(string $table, bool $onlyNames = false): array
    {
        if (empty($table)) {
            return [];
        }
        global $msc;
        static $cache;
        $cacheId = $table;
        if (!isset($cache[$cacheId])) {
            $cache[$cacheId] = [];
            $result = self::fetchFields($table);
            if (!$result) {
                return [];
            }
            foreach ($result as $row) {
                $cache[$cacheId] [$row->Field] = $row;
            }
        }
        if ($onlyNames) {
            return array_keys($cache[$cacheId]);
        } else {
            return $cache[$cacheId];
        }
    }

    /**
     * Массив полей
     * @return FieldInfo[]
     */
    private static function fetchFields(string $table): array
    {
        global $msc;
        $table = str_replace('`', '``', $table);
        return $msc->driver->getFields($table);
    }

    /**
     * Возвращает массив ключей таблицы в виде двумерного массива ([Поле][Имя ключа]
     *
     * @param string $table Имя таблицы
          * @return array
     *@package sql
     */
    public static function getTableKeys(string $table): array
    {
        global $msc;
        return $msc->driver->getKeys($table);
    }

    /**
     * Удаляет ключевое поле из таблицы, предварительно удаляя параметр auto_increment если есть
     *
     * @package sql
     * @param string  Имя таблицы
     * @return boolean Удачно или нет. Если PRIMARY KEY нет, возвращает пустую строку
     */
    public static function dropPrimaryKey($tbl)
    {
        global $msc;
        $fields = self::getFields($tbl);
        foreach ($fields as $f) {
            if ($f->Key == 'PRI') {
                $definition = self::getFieldDefinition($f);
                $field      = $f->Field;
            }
        }
        if (isset($definition)) {
            if (stristr($definition, 'auto_increment')) {
                $definition = str_ireplace('auto_increment', '', $definition);
                $sql = 'ALTER TABLE `' . $tbl . '` CHANGE ' . $field . ' ' . $field . ' ' . $definition;
                $msc->execPdo($sql);
            }
            $sql = "ALTER TABLE `$tbl` DROP PRIMARY KEY";
            if ($msc->execPdo($sql)) {
                return $msc->success('Ключ удален', $sql);
            } else {
                return $msc->error('Ошибка удаления ключа', $sql);
            }
        }
        return '';
    }

    /**
     * Создаёт определение поля из объекта или на основе параметров, со свойствами поля (field, type...)
     *
     * @package sql
     * @param mixed  Либо field-объект, либо тип поля (в случае указания параметров по отдельности)
     * @param string Значение Null field-объекта (YES|NO - строка, определяющая, является ли поле NULL)
     * @param string Значение по умолчанию
     * @param string Значение Extra field-объекта
     * @param string Длина поля, если необходимо
     * @return string Определение поля (field definition)
     */
    public static function getFieldDefinition(
        $type = null,
        $null = null,
        $default = null,
        $extra = null,
        $length = null
    ) {
        if (is_object($type)) {
            foreach ($type as $param => $value) {
                $param = strtolower($param);
                $$param = $value;
            }
            if (stristr($type, 'UNSIGNED')) {
                $extra .= ' UNSIGNED';
                $type   = str_ireplace('UNSIGNED', '', $type);
            }
            if (stristr($type, 'ZEROFILL')) {
                $extra .= ' ZEROFILL';
                $type = str_ireplace('ZEROFILL', '', $type);
            }
            if (preg_match('~\((.*)\)~U', $type, $length)) {
                $length = $length[1];
                $type = trim(str_replace('(' . $length . ')', '', $type));
            }
        }
        $type = strtoupper($type);
        // особый тип, без доп. параметров
        if ($type == 'SERIAL') {
            return 'SERIAL';
        }
        if ($type == 'VARCHAR') {
            if (!is_numeric($length) || $length > 255 || $length < 1) {
                $length = 255;
            }
            $type .= "($length)";
        } elseif ($type == 'SET' || $type == 'ENUM') {
            if (empty($length)) {
                return false;
            }
            $type .= "($length)";
        } elseif ($type == 'FLOAT' || $type == 'DOUBLE') {
            if (empty($length)) {
                return false;
            } else {
                $length = str_replace('.', ',', $length);
            }
            $type .= "($length)";
        } elseif (is_numeric($length) && !stristr($type, 'text')) {
            $type .= "($length)";
        }
        $field_info  = $type;
        if (stristr($extra, 'UNSIGNED')) {
            // это алиас
            if ($type != 'BOOLEAN') {
                $field_info .= ' UNSIGNED';
            }
            $extra = str_replace('UNSIGNED', '', $extra); // UNSIGNED - после типа поля
        }
        if (stristr($extra, 'ZEROFILL')) {
            $field_info .= ' ZEROFILL';
            $extra = str_replace('ZEROFILL', '', $extra);
        }
        if ($null != 'YES') {
            $field_info .=  ' NOT NULL';
        }
        if (trim($extra) != null) {
            $field_info .= ' ' . $extra;
        }
        if ($default != null) {
            if (is_numeric($default)) {
                $field_info .=  ' DEFAULT ' . intval($default);
            } else {
                $field_info .=  ' DEFAULT "' . $default . '"';
            }
        }
        return str_ireplace('auto_increment', 'AUTO_INCREMENT', $field_info);
    }

    /**
     * @return array
     */
    public static function getCashedTablesArray(): array
    {
        static $array;
        if (!isset($array)) {
            global $msc;
            $array = $msc->driver->getTables();
        }
        return $array;
    }

    /**
     * Возвращает массив таблиц базы данных
     *
     * @param string База данных
     * @return array
     */
    public static function getTables($database = null)
    {
        global $msc;
        if (!is_null($database)) {
            $msc->selectDb($database);
        }

        $tables = self::getCashedTablesArray();
        $array = [];
        foreach ($tables as $o) {
            $array[] = $o->Name;
        }
        return $array;
    }

    /**
     * Удаляет $limit строк из $table по условию $row
     *
     * @param string
     * @param string
     * @param string $row
     * @param integer
     * @return boolean
     * @throws \Exception
     */
    public function rowDelete($db, $table, $row): bool
    {
        global $msc;
        if (!$this->validate->queryCheck($db, $table, $row)) {
            return false;
        };
        $row = stripslashes(urldecode($row));
        $sql = 'DELETE FROM ' . $table . ' WHERE ' . $row;
        return $msc->execPdo($sql);
    }

    /**
     * Копирует строки $table по условию $row
     *
     * @param string
     * @param string
     * @param string
     * @return boolean
     * @throws \Exception
     */
    public function rowCopy($table, $row)
    {
        global $msc;
        $fields = self::getFields($table);
        $fieldsWithoutKey = [];
        $ai = null;
        foreach ($fields as $field) {
            if (!strchr($field->Key, 'PRI')) {
                $fieldsWithoutKey [] = $field->Field;
            }
            if ($field->Extra != null) {
                $ai = $field->Field;
            }
        }
        if (!$ai) {
            return $msc->error('Невозможно скопировать ряд, т.к. в таблице нет поля auto_increment');
        }
        $fields = implode(',', $fieldsWithoutKey);
        $row = stripslashes(urldecode($row));
        $sql = "INSERT INTO $table ($fields) SELECT $fields FROM $table WHERE $row";
        return $msc->execPdo($sql);
    }
}
