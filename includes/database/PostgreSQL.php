<?php

namespace database;

/**
 *
 */
class PostgreSQL implements Driver {
    public function __construct()
    {
    }

    public function getDatabases(): array
    {
        global $msc;
        return [$msc->db];
    }

    public function getTables(): array
    {
        global $msc;
        $sql = '
            SELECT 
                table_name as "Name",
                \'\' as "Engine",
                0 as "Rows",
                0 as "Data_length",
                0 as "Index_length",
                0 as "Auto_increment",
                0 as "Create_time",
                0 as "Update_time",
                \'\' as "Collation"
            FROM information_schema.tables 
            WHERE table_schema=\'public\' OR table_schema=\''.$msc->db.'\' 
            ORDER BY table_name';
        return $msc->getData($sql, \PDO::FETCH_OBJ);
    }

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
                WHERE table_name = \''.$table.'\'';

        $keys = $this->getKeys($table);
        $fields = $msc->getData($sql, \PDO::FETCH_OBJ);
        foreach ($fields as $field) {
            $fieldKeys = $keys[$field->Field];
            if (!$fieldKeys) {
                continue;
            }
            foreach ($fieldKeys as $key => $type) {
                $field->Key = $type;
            }
        }
        return $fields;
    }

    public function getKeys(string $table): array
    {
        global $msc;
        if (empty($table)) {
            return [];
        }
        $sql = "
            SELECT *
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
            ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
            WHERE tc.table_name = '$table' AND tc.table_schema = 'public'
            ORDER BY kcu.ordinal_position; ";
        $keys = [];
        $result = $msc->getData($sql);
        if (!$result) {
            return [];
        }
        foreach ($result as $row) {
            if ($row['constraint_type'] == 'PRIMARY KEY') {
                $keys[$row['column_name']][$row['constraint_name']] = 'PRI';
            } else {
                $keys[$row['column_name']][$row['constraint_name']] = $row['constraint_type'] == 'UNIQUE' ? 'UNI' : 'MUL';
            }
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
            WHERE table_name=\''.$table.'\' and (table_schema=\'public\' OR table_schema=\''.$msc->db.'\')
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

    public function getTableInfo(string $table): array
    {
        $data = [
            'Auto_increment' => $this->getAutoIncrement($table),
            'Charset' => $this->getCharset(),
            'Comment' => $this->getComment($table),
        ];
        return $data;
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
            WHERE table_name = \''.$table.'\' AND is_identity = \'YES\'';
        $autoIncrement = $msc->getData($sql);
        if ($autoIncrement) {
            return $autoIncrement[0]['identity_start'];
        }
        return '';
    }
}