<?php

declare(strict_types=1);

namespace database;

use dto\FieldInfo;
use dto\TableInfo;
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
     * @param string|null $db
     * @param string|null $table
     */
    public function __construct(?string $db = null, ?string $table = null)
    {
        $this->database = $db;
        $this->table = $table ? '`' . str_replace('`', '``', $table) . '`' : '';
        $this->validate = new Validate();
    }

    /**
     * Совершает действие типа $type с $table, используя если надо параметр $param
     *
     * @param string $db
     * @param string $table
     * @param string $type DROP | TRUNCATE | ANALISE | OPTIMIZE | CHECK | REPAIR | FLUSH
     * @param string $param
     * @return bool
     * @throws \Exception
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
                if ($msc->driverName == 'pgsql') {
                    $sql = "ALTER TABLE `$table` RENAME TO `$param`";
                } else {
                    $sql = "ALTER TABLE `$table` RENAME `$param`";
                }
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
        if (in_array($type, ['CHECK', 'ANALYZE', 'REPAIR', 'OPTIMIZE', 'FLUSH'])) {
            $result = $msc->fetchPdo($sql);
        } else {
            $result = $msc->execPdo($sql);
        }
        if ($result) {
            if (in_array($type, ['TRUNCATE'])) {
                $msc->fetchPdo("ANALYZE TABLE `$table`;");
            }
            return $msc->success("Таблица $table $text", $sql);
        } else {
            $text = "Ошибка при выполнении операции с таблицей $table";
            return $msc->error($text, $sql);
        }
    }

    /**
     * Копирует таблицу $table, если надо со структурой, данными. если надо переименовывает
     *
     * @param string $db текущая БД
     * @param string $table таблица
     * @param bool $struct надо ли создавать таблицу на новом месте
     * @param bool $data надо ли копировать данные
     * @param string|null $newName новое имя, если таблица переименовывается
     * @param string|null $database База данных, куда надо копировать
     * @return bool
     * @throws \Exception
     */
    public function copyTable(string $db, string $table, bool $struct = true, bool $data = false, ?string $newName = null, ?string $database = null): bool
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

        if ($struct) {
            if ($msc->driverName == 'pgsql') {
                $sql = "CREATE TABLE $newName (
                    LIKE $table INCLUDING ALL
                );";
            } else {
                $exp = new Export();
                $exp->setDatabase($db);
                $exp->setTable($table);
                $sql = $exp->exportStructure(true);
                $sql = preg_replace(
                    '/CREATE TABLE ([a-zA-Z0-9_`\-]+)/i',
                    'CREATE TABLE `' . $newName . '`',
                    $sql,
                    1
                );
            }

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
        }

        // дамп данных
        if ($data) {
            $add = '';
            if ($msc->driverName == 'pgsql') {
                $add = ' OVERRIDING SYSTEM VALUE';
            }
            if ($database != $db) {
                $sql = 'INSERT INTO ' . $database . '.' . $newName . ' ' . $add . ' SELECT * FROM ' . $db . '.' . $table;
            } else {
                $sql = "INSERT INTO $newName $add SELECT * FROM $table";
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
     * @return FieldInfo[] Массив полей
     */
    public static function getFields(string $table): array
    {
        if (empty($table)) {
            return [];
        }
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
        return $cache[$cacheId];
    }

    /**
     * Только массив имен полей
     * @param string $table
     * @return array<string>
     */
    public static function getFieldNames(string $table): array
    {
        $fields = self::getFields($table);
        return array_keys($fields);
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
     * @return array<array<string>>
     */
    public static function getTableKeys(string $table): array
    {
        global $msc;
        return $msc->driver->getKeys($table);
    }

    /**
     * Удаляет ключевое поле из таблицы, предварительно удаляя параметр auto_increment если есть
     *
     * @param string $tbl Имя таблицы
     * @return bool Удачно или нет. Если PRIMARY KEY нет, возвращает пустую строку
     * @throws \Exception
     */
    public static function dropPrimaryKey(string $tbl): bool
    {
        global $msc;
        $fields = self::getFields($tbl);
        $primaryKey = $definition = '';
        foreach ($fields as $field) {
            if ($field->Key == 'PRI') {
                $definition = self::getFieldDefinitionFromObject($field);
                $primaryKey = $field->Field;
            }
        }
        if ($primaryKey) {
            if (stristr($definition, 'auto_increment')) {
                $definition = str_ireplace('auto_increment', '', $definition);
                $sql = 'ALTER TABLE `' . $tbl . '` CHANGE ' . $primaryKey . ' ' . $primaryKey . ' ' . $definition;
                $msc->execPdo($sql);
            }
            $sql = "ALTER TABLE `$tbl` DROP PRIMARY KEY";
            if ($msc->execPdo($sql)) {
                return $msc->success('Ключ удален', $sql);
            } else {
                return $msc->error('Ошибка удаления ключа', $sql);
            }
        }
        return false;
    }

    /**
     * Создаёт определение поля из объекта или на основе параметров, со свойствами поля (field, type...)
     *
     * @param mixed|null $type Либо field-объект, либо тип поля (в случае указания параметров по отдельности)
     * @param string|null $null Значение Null field-объекта (YES|NO - строка, определяющая, является ли поле NULL)
     * @param string|null $default Значение по умолчанию
     * @param string|null $extra Значение Extra field-объекта
     * @param string|null $length Длина поля, если необходимо
     * @return string Определение поля (field definition)
     */
    public static function getFieldDefinition(
        mixed $type = null,
        ?string $null = null,
        ?string $default = null,
        ?string $extra = null,
        ?string $length = null
    ): string {
        $type = strtoupper($type);
        // особый тип, без доп. параметров
        if ($type == 'SERIAL') {
            return 'SERIAL';
        }
        // Добавляем length к типу
        if ($type == 'VARCHAR') {
            if (!is_numeric($length) || $length > 255 || $length < 1) {
                $length = 255;
            }
            $type .= "($length)";
        } elseif ($type == 'SET' || $type == 'ENUM') {
            if (empty($length)) {
                return '';
            }
            $type .= "($length)";
        } elseif ($type == 'FLOAT' || $type == 'DOUBLE') {
            if (empty($length)) {
                return '';
            } else {
                $length = str_replace('.', ',', $length);
            }
            $type .= "($length)";
        } elseif (is_numeric($length) && !stristr($type, 'text')) {
            $type .= "($length)";
        }
        // Начинаем собирать fieldInfo
        $field_info  = $type;
        // Null
        $isNull = $null == 'YES';
        if (!$isNull) {
            $field_info .=  ' NOT NULL';
        }
        // Unsigned
        if ($extra != null) {
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
            $field_info .= ' ' . $extra;
        }
        // default
        if ($default != null) {
            if (is_numeric($default)) {
                $field_info .=  ' DEFAULT ' . intval($default);
            } elseif (strpos($default, '::')) {
                $field_info .=  ' DEFAULT ' . $default ;
            } else {
                $field_info .=  ' DEFAULT "' . $default . '"';
            }
        } elseif (!$isNull) {
            // для pgsql, но может и для mysql сойдет
            if ((str_contains($type, 'CHAR') || $type == 'TEXT')) {
                $field_info .=  " DEFAULT ''";
            }
            if ($type == 'BOOLEAN') {
                $field_info .=  " DEFAULT TRUE";
            }
            if (str_contains($type, 'INT') ) {
                $field_info .=  " DEFAULT 0";
            }
        }
        return str_ireplace('auto_increment', 'AUTO_INCREMENT', $field_info);
    }

    /**
     * @param FieldInfo $type
     * @return string
     */
    public static function getFieldDefinitionFromObject(FieldInfo $type): string
    {
        $null = $type->Null;
        $default = $type->Default;
        $extra = $type->Extra;
        $type = $type->Type;
        $length = null;
        if (stristr($type, 'UNSIGNED')) {
            $extra .= ' UNSIGNED';
            $type   = str_ireplace('UNSIGNED', '', $type);
        }
        if (stristr($type, 'ZEROFILL')) {
            $extra .= ' ZEROFILL';
            $type = str_ireplace('ZEROFILL', '', $type);
        }
        if (preg_match('~\((.*)\)~U', $type, $a)) {
            $length = $a[1];
            $type = trim(str_replace('(' . $length . ')', '', $type));
        }
        return self::getFieldDefinition($type, $null, $default, $extra, $length);
    }

    /**
     * @return TableInfo[]
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
     * @param string|null $database База данных
     * @return array<string>
     * @throws \Exception
     */
    public static function getTables(?string $database = null): array
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
     * @param string $db
     * @param string $table
     * @param string $row
     * @return bool
     * @throws \Exception
     */
    public function rowDelete(string $db, string $table, string $row): bool
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
     * @param string $table
     * @param string $row
     * @return bool
     * @throws \Exception
     */
    public function rowCopy(string $table, string $row): bool
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
