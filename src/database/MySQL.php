<?php

declare(strict_types=1);

namespace database;

use dto\Constraint;
use dto\FieldInfo;
use dto\KeyInfo;
use dto\TableInfo;

class MySQL implements Driver
{
    public function __construct()
    {
    }

    /**
     * @return array<array<string>>
     *
     * @throws \Exception
     */
    public function getDatabases(): array
    {
        global $msc;

        return $msc->getData('SHOW DATABASES');
    }

    /**
     * @return array<string>
     *
     * @throws \Exception
     */
    public function getDatabaseNames(): array
    {
        global $msc;

        return $msc->getData('SHOW DATABASES', \PDO::FETCH_COLUMN);
    }

    /**
     * @return TableInfo[]
     *
     * @throws \Exception
     */
    public function getTables(string $db = ''): array
    {
        global $msc;
        $sql = 'SHOW TABLE STATUS';
        if ($db) {
            $sql .= " FROM $db";
        }
        $tables = $msc->getData($sql, \PDO::FETCH_OBJ);
        foreach ($tables as $key => $value) {
            $tableInfo = new TableInfo();
            $tableInfo->fill((array) $value);
            $tables[$key] = $tableInfo;
        }

        return $tables;
    }

    /**
     * @return FieldInfo[]
     *
     * @throws \Exception
     */
    public function getFields(string $table): array
    {
        global $msc;
        if (empty($table)) {
            return [];
        }
        $fields = $msc->getData('SHOW FIELDS FROM `'.$table.'`', \PDO::FETCH_OBJ);
        foreach ($fields as $key => $value) {
            $fieldInfo = new FieldInfo();
            $fieldInfo->fill((array) $value);
            $fields[$key] = $fieldInfo;
        }

        return $fields;
    }

    /**
     * @return array<string, array<string, string>>
     *
     * @throws \Exception
     */
    public function getKeys(string $table): array
    {
        $result = $this->getKeysFull($table);
        $keys = [];
        foreach ($result as $row) {
            if ($row->Key_name == 'PRIMARY') {
                $keys[$row->Column_name][$row->Key_name] = 'PRI';
            } else {
                $keys[$row->Column_name][$row->Key_name] = $row->Non_unique == 0 ? 'UNI' : 'MUL';
            }
        }

        return $keys;
    }

    /**
     * @return array<KeyInfo>
     */
    public function getKeysFull(string $table): array
    {
        global $msc;
        if (empty($table)) {
            return [];
        }

        return $msc->getData('SHOW KEYS FROM `'.$table.'`', \PDO::FETCH_OBJ);
    }

    /**
     * @return array<Constraint>
     *
     * @throws \Exception
     */
    public function getConstraints(string $table): array
    {
        global $msc;
        $sql = '
                SELECT i.*, k.*  
                FROM information_schema.TABLE_CONSTRAINTS i
                LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME 
                WHERE i.TABLE_SCHEMA = \''.$msc->db.'\' AND i.TABLE_NAME = \''.$table.'\'
                GROUP BY k.CONSTRAINT_NAME';

        return $msc->getData($sql, \PDO::FETCH_OBJ);
    }

    /**
     * @throws \Exception
     */
    public function selectDb(string $db): void
    {
        global $msc;
        $msc->execPdo("USE `$db`");
    }

    /**
     * @throws \Exception
     */
    public function sqlCreateTable(string $table): string
    {
        global $msc;
        $res = $msc->fetchPdo('SHOW CREATE TABLE '.$msc->table);
        if (!$res) {
            return '';
        }

        return $res->fetch()['Create Table'];
    }

    /**
     * Возвращает таблицу с полной информацией о таблице $this->table.
     *
     * @return array<array<string>>
     *
     * @throws \Exception
     */
    public function getTableDetailsWithComments(string $table): array
    {
        global $msc;
        $comments = [
            'Engine' => 'Тип хранилища',
            'Version' => 'Версия .frm файла таблицы',
            'Row_format' => 'Формат хранения строки (Fixed, Dynamic, Compressed, Redundant, Compact). 
                Начиная с MySQL/InnoDB 5.0.3, InnoDB таблицы хранятся в форматах Redundant или Compact. 
                До 5.0.3, InnoDB таблицы всегда были в формате Redundant',
            'Rows' => 'Количество рядов. Некоторые типы хранилищ, такие как MyISAM, 
                отображают точное количество. Но в некоторых других, таких как InnoDB, 
                это значение является приблизительным и может отличаться  от действительного количество на 40-50%.
                В таких случаях лучше всего использовать запрос SELECT COUNT(*). 
                Также это значение равно NULL для таблиц INFORMATION_SCHEMA базы данных',
            'Avg_row_length' => 'Средняя длина строки',
            'Data_length' => 'Размер файла данных таблицы',
            'Max_data_length' => 'Максимальный размер файла данных. Это общее количество байтов данных, которое 
                может быть сохранено в таблице, given the data pointer size used.',
            'Index_length' => 'Размер индексного файла',
            'Data_free' => 'Размер занятого, но не использованного пространства',
            'Auto_increment' => 'Следующее значение поля Auto_increment',
            'Update_time' => 'Когда дата файл был обновлён. Для некоторых типов хранилищ, это значение NULL. 
                Например, InnoDB хранит таблицы в собственном хранилище и время изменения файла данных не даст ничего',
            'Check_time' => 'Когда таблицы были проверены в последний раз. Не все типы хранилищ обновляют 
                этот параметр, в этих случаях он всегда NULL',
            'Collation' => 'Кодировка и сравнение таблиц',
            'Checksum' => 'The live checksum value (if any).',
            'Create_options' => 'Дополнительные опции, заданные при создании таблицы через CREATE TABLE.',
            'Comment' => 'Комментарий, заданный при создании таблицы (либо информация о том, почему MySQL 
                не может получить доступ к информации о таблице',
        ];
        $sql = 'SHOW TABLE STATUS LIKE "'.$table.'"';
        $result = $msc->getData($sql);
        foreach ($comments as $k => &$v) {
            $v = str_replace('', '', $v);
            $v = str_replace(' title=', '', $v);
        }

        return [$comments, $result];
    }

    /**
     * @return array<array<string>>
     *
     * @throws \Exception
     */
    public function getCharsets(): array
    {
        global $msc;

        return $msc->getData('SHOW CHARACTER SET');
    }

    /**
     * @return array<array<string>>
     *
     * @throws \Exception
     */
    public function getProcessList(): array
    {
        global $msc;

        return $msc->getData('SHOW FULL PROCESSLIST');
    }

    /**
     * @throws \Exception
     */
    public function getTableInfo(string $table): TableInfo
    {
        global $msc;
        $result = $msc->fetchPdo('SHOW TABLE STATUS FROM '.$msc->db.' LIKE "'.$table.'"');
        if (!$result) {
            return new TableInfo();
        }
        $row = $result->fetchObject();
        if ($row->Collation) {
            $row->Charset = explode('_', $row->Collation)[0];
        } else {
            $row->Charset = '';
        }
        $tableInfo = new TableInfo();
        $tableInfo->fill((array) $row);

        return $tableInfo;
    }

    /**
     * @return array<string>
     */
    public function getIdentityInfo(string $table): array
    {
        return [];
    }
}
