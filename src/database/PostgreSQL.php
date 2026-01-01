<?php

declare(strict_types=1);

namespace database;

use dto\FieldInfo;
use dto\TableInfo;
use stdClass;

/**
 *
 */
class PostgreSQL implements Driver
{
    public function __construct()
    {
    }

    public function getDatabases(): array
    {
        global $msc;
        return $msc->getData('SELECT datname FROM pg_database WHERE datistemplate = false', \PDO::FETCH_COLUMN);
    }

    public function getTables(string $db = ''): array
    {
        global $msc;
        if (!$db) {
            $db = $msc->db;
        }

        $sql = '
            SELECT identity_start, table_name
            FROM information_schema.columns 
            WHERE is_identity = \'YES\'';
        $data = $msc->getData($sql);
        $autoIncrementsByTables = [];
        foreach ($data as $value) {
            $autoIncrementsByTables [$value['table_name']] = $value['identity_start'];
        }

        $charset = $this->getCharset();

        // Выполнить analize, чтобы обновить статистику количества строк
        if ($msc->page == 'tbl_list') {
            $sql = '
            SELECT *
            FROM information_schema.tables 
            WHERE table_schema=\'public\' OR table_schema=\'' . $db . '\'';
            $data = $msc->getData($sql, \PDO::FETCH_OBJ);
            foreach ($data as $key => $value) {
                $msc->execPdo('ANALYZE "' . $value->table_name . '"');
            }
        }

        $sql = '
            SELECT 
                table_name as "Name",
                \'PostgreSQL\' as "Engine",
                c.reltuples::bigint as "Rows",
                pg_total_relation_size(c.oid) as "Data_length",
                0 as "Index_length",
                0 as "Auto_increment",
                0 as "Create_time",
                0 as "Update_time",
                \'' . $charset . '\' as "Collation",
                table_schema as "Schema"
            FROM information_schema.tables ist 
                LEFT JOIN pg_class c ON c.relname = ist.table_name
                LEFT JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE table_schema=\'public\' OR table_schema=\'' . $db . '\' 
                AND nspname NOT IN (\'pg_catalog\', \'information_schema\') AND relkind = \'r\'
            ORDER BY table_name';
        $data = $msc->getData($sql, \PDO::FETCH_OBJ);

        foreach ($data as $key => $value) {
            $value->Auto_increment = $autoIncrementsByTables[$value->Name];
        }

        return $data;
    }

    /**
     * @param string $table
     * @return FieldInfo[]
     * @throws \Exception
     */
    public function getFields(string $table): array
    {
        global $msc;
        if (empty($table)) {
            return [];
        }
        $sql = '
                SELECT 
                    column_name AS "Field",
                    data_type AS "Type",
                    is_nullable AS "Null",
                    column_default AS "Default",
                    \'\' AS "Key",
                    CASE
                        WHEN is_identity = \'YES\' THEN \'auto\'
                        ELSE null
                    END AS "Extra"
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE table_name = \'' . $table . '\'';

        $keys = $this->getKeys($table);
        $fields = $msc->getData($sql, \PDO::FETCH_OBJ);
        foreach ($fields as $key => $field) {
            $fieldKeys = $keys[$field->Field];
            if ($fieldKeys) {
                foreach ($fieldKeys as $type) {
                    $field->Key = $type;
                }
            }
            $fieldInfo = new FieldInfo();
            $fieldInfo->fill($field);
            $fields [$key] = $fieldInfo;
        }
        return $fields;
    }

    public function getKeys(string $table, bool $full = false): array
    {
        if (empty($table)) {
            return [];
        }
        $constraintsGrouped = $this->getConstraints($table, full: true);
        $result = [];
        foreach ($constraintsGrouped as $constraint) {
            $key = new stdClass();
            $key->Table = $constraint->table_name;
            $key->Non_unique = $constraint->constraint_type == 'UNIQUE' ? 0 : 1;
            $key->Key_name = $constraint->constraint_type;
            $key->Seq_in_index = $constraint->ordinal_position;
            $key->Column_name = $constraint->column_name;
            $key->Collation = '';
            $key->Cardinality = '';
            $key->Sub_part = '';
            $key->Packed = '';
            $key->Null = '';
            $key->Index_type = ''; // BTREE
            $key->Comment = '';
            $key->Index_comment = '';
            $result [] = $key;
        }
        if (!$result) {
            return [];
        }
        if ($full) {
            return $result;
        }
        foreach ($result as $row) {
            if ($row->Key_name == 'PRIMARY KEY') {
                $keys [$row->Column_name][$row->Key_name] = 'PRI';
            } else {
                $keys [$row->Column_name][$row->Key_name] = $row->Non_unique == 0 ? 'UNI' : 'MUL';
            }
        }
        return $keys;
    }

    public function getConstraints(string $table, bool $full = false): array
    {
        global $msc;
        if (empty($table)) {
            return [];
        }
        $sql = "
            SELECT 
                *
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
            ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
            WHERE tc.table_name = '$table' AND (tc.table_schema = 'public' OR tc.table_schema = '$msc->db')
            ORDER BY kcu.ordinal_position; ";
        $keys = [];
        $data = $msc->getData($sql, \PDO::FETCH_OBJ);
        if ($full) {
            return $data;
        }
        foreach ($data as $obj) {
            $cloned = new stdClass();
            foreach ($obj as $key => $value) {
                $keyUpper = strtoupper($key);
                $cloned->{$keyUpper} = $obj->{$key};
            }
            $keys[$cloned->CONSTRAINT_TYPE][] = $cloned;
        }
        return $keys;
    }

    public function selectDb(string $db)
    {
        global $msc;
        if ($db != $msc->db) {
            throw new \Exception('In PostgreSQL, you cannot change the current database within
                an existing PDO connection');
        }
    }

    public function sqlCreateTable(string $table): string
    {
        return '';
    }

    public function getTableDetailsWithComments(string $table): array
    {
        global $msc;
        $comments = [
            'table_catalog' => 'table_catalog',
            'table_schema' => 'table_schema',
            'table_name' => 'table_name',
            'table_type' => 'table_type',
            'self_referencing_column_name' => 'self_referencing_column_name',
            'reference_generation' => 'reference_generation',
            'user_defined_type_catalog' => 'user_defined_type_catalog',
            'user_defined_type_schema' => 'user_defined_type_schema',
            'user_defined_type_name' => 'user_defined_type_name',
            'is_insertable_into' => 'is_insertable_into',
            'is_typed' => 'is_typed',
            'commit_action' => 'commit_action',
        ];
        $sql = '
            SELECT *
            FROM information_schema.tables 
            WHERE table_name=\'' . $table . '\' and (table_schema=\'public\' OR table_schema=\'' . $msc->db . '\')
            ORDER BY table_name';
        $result = $msc->getData($sql, \PDO::FETCH_OBJ);

        return [$comments, $result];
    }
    /**
     * This view usually only shows a single entry for the current database's encoding,
     * as PostgreSQL does not support multiple character sets within one database.
     */
    public function getCharsets(): array
    {
        global $msc;
        //$data = $msc->getData('SHOW SERVER_ENCODING;');
        //$data = $msc->getData('SHOW CLIENT_ENCODING;');
        //return $msc->getData('SELECT character_set_name as "Charset" FROM information_schema.character_sets;');
        return [];
    }

    public function getProcessList(): array
    {
        global $msc;
        return $msc->getData('SELECT * FROM pg_stat_activity');
    }

    public function getTableInfo(string $table): TableInfo
    {
        $data = [
            'Auto_increment' => $this->getAutoIncrement($table),
            'Charset' => $this->getCharset(),
            'Comment' => $this->getComment($table),
        ];
        $tableInfo = new TableInfo();
        $tableInfo->fill($data);
        return $tableInfo;
    }

    private function getComment($table): string
    {
        global $msc;
        $sql = "
            SELECT d.description
            FROM pg_catalog.pg_class c
            JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
            LEFT JOIN pg_catalog.pg_description d ON d.objoid = c.oid AND d.objsubid = 0
            WHERE c.relname = '$table' AND n.nspname = 'public'";
        $data = $msc->getData($sql);
        if (!$data) {
            return '';
        }
        return $data[0]['description'] ?? '';
    }

    /**
     * In PostgreSQL, character set encoding is defined at the database level, not the table level.
     * All tables within a single database share the same encoding.
     */
    private function getCharset(): string
    {
        global $msc;
        $sql = 'SELECT pg_encoding_to_char(encoding) as "charset" FROM pg_database WHERE datname = current_database();';
        $data = $msc->getData($sql);
        if (!$data) {
            return '';
        }
        return $data[0]['charset'];
    }

    private function getAutoIncrement(string $table): string
    {
        global $msc;
        $sql = '
            SELECT identity_start
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE table_name = \'' . $table . '\' AND is_identity = \'YES\'';
        $autoIncrement = $msc->getData($sql);
        if ($autoIncrement) {
            return $autoIncrement[0]['identity_start'];
        }
        return '';
    }
}
