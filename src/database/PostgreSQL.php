<?php

declare(strict_types=1);

namespace database;

use dto\Constraint;
use dto\FieldInfo;
use dto\KeyInfo;
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

    /**
     * @return array<array<string>>
     * @throws \Exception
     */
    public function getDatabases(): array
    {
        global $msc;
        return $msc->getData('SELECT * FROM pg_database');
    }

    /**
     * @return array<string>
     * @throws \Exception
     */
    public function getDatabaseNames(): array
    {
        global $msc;
        return $msc->getData('SELECT datname FROM pg_database WHERE datistemplate = false', \PDO::FETCH_COLUMN);
    }

    /**
     * @param string $db
     * @return TableInfo[]
     */
    public function getTables(string $db = ''): array
    {
        global $msc;
        $oldDatabase = false;
        if (!$db) {
            $db = $msc->db;
        } elseif ($db != $msc->db) {
            $oldDatabase = $msc->db;
            try {
                $config = $msc->getConfig();
                $msc->connectPdo($config, $db);
            } catch (\PDOException $e) {
                $msc->error("Не смог соединиться с БД $db, чтобы получить инфо таблиц");
                return [];
            }
        }

        if ($msc->page == 'tbl_list') {
            $this->updateStatistics($db);
        }

        $data = $this->fetchTables($db);

        $autoIncrementsByTables = $this->getAutoIncrements();
        foreach ($data as $value) {
            if (array_key_exists($value->Name, $autoIncrementsByTables)) {
                $value->Auto_increment = $autoIncrementsByTables[$value->Name];
            }
        }

        if ($oldDatabase) {
            $config = $msc->getConfig();
            $msc->connectPdo($config, $oldDatabase);
        }

        return $data;
    }

    /**
     * Непосредственно запрос на выборку таблиц без лишнего кода
     * @param string $db
     * @return array<TableInfo>
     * @throws \Exception
     */
    private function fetchTables(string $db): array
    {
        global $msc;
        $charset = $this->getCharset();
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
        return $msc->getData($sql, \PDO::FETCH_OBJ);
    }

    /**
     * Статистика по autoIncrements
     * @return array<string, string>
     */
    private function getAutoIncrements(): array
    {
        global $msc;
        $sql = '
            SELECT identity_start, table_name
            FROM information_schema.columns 
            WHERE is_identity = \'YES\'';
        $data = $msc->getData($sql);
        $autoIncrementsByTables = [];
        foreach ($data as $value) {
            $autoIncrementsByTables [$value['table_name']] = $value['identity_start'];
        }
        return $autoIncrementsByTables;
    }

    /**
     * Выполнить analyze, чтобы обновить статистику количества строк
     */
    private function updateStatistics(string $db): void
    {
        global $msc;
        $sql = '
            SELECT *
            FROM information_schema.tables 
            WHERE table_schema=\'public\' OR table_schema=\'' . $db . '\'';
        $data = $msc->getData($sql, \PDO::FETCH_OBJ);
        foreach ($data as $key => $value) {
            $msc->execPdo('ANALYZE "' . $value->table_name . '"');
        }
    }

    /**
     * @param string $table
     * @return FieldInfo[]
     * @throws \Exception
     */
    public function getFields(string $table): array
    {
        if (empty($table)) {
            return [];
        }
        $fields = $this->fetchFields($table);
        $keys = $this->getKeys($table);
        foreach ($fields as $field) {
            if (array_key_exists($field->Field, $keys)) {
                $fieldKeys = $keys[$field->Field];
                foreach ($fieldKeys as $type) {
                    $field->Key = $type;
                }
            }
        }
        return $fields;
    }

    /**
     * @param string $table
     * @return FieldInfo[]
     * @throws \Exception
     */
    public function fetchFields(string $table): array
    {
        global $msc;
        $sql = '
                SELECT 
                    column_name AS "Field",
                    data_type AS "Type",
                    is_nullable AS "Null",
                    column_default AS "Default",
                    \'\' AS "Key",
                    CASE
                        WHEN is_identity = \'YES\' THEN \'AUTO_INCREMENT\'
                        ELSE null
                    END AS "Extra"
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE table_name = \'' . $table . '\'';
        return $msc->getData($sql, \PDO::FETCH_OBJ);
    }

    /**
     * @param string $table
     * @return array<string, array<string, string>>
     */
    public function getKeys(string $table): array
    {
        $result = $this->getKeysFull($table);
        $keys = [];
        foreach ($result as $row) {
            if ($row->Key_name == 'PRIMARY KEY') {
                $keys [$row->Column_name][$row->Key_name] = 'PRI';
            } else {
                $keys [$row->Column_name][$row->Key_name] = $row->Non_unique == 0 ? 'UNI' : 'MUL';
            }
        }
        return $keys;
    }

    /**
     * @param $table
     * @return array<KeyInfo>
     */
    public function getKeysFull($table): array
    {
        if (empty($table)) {
            return [];
        }
        $constraints = $this->getConstraints($table);
        $result = [];
        foreach ($constraints as $constraint) {
            $key = new KeyInfo();
            $key->Table = $constraint->TABLE_NAME;
            $key->Non_unique = $constraint->CONSTRAINT_TYPE == 'UNIQUE' ? 0 : 1;
            $key->Key_name = $constraint->CONSTRAINT_TYPE;
            $key->Seq_in_index = $constraint->ORDINAL_POSITION;
            $key->Column_name = $constraint->COLUMN_NAME;
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
        return $result;
    }

    /**
     * @param string $table
     * @return array<Constraint>
     * @throws \Exception
     */
    public function getConstraints(string $table): array
    {
        global $msc;
        if (empty($table)) {
            return [];
        }
        $sql = "
            SELECT 
                tc.table_schema AS \"TABLE_SCHEMA\",
                tc.table_name AS \"TABLE_NAME\",
                tc.constraint_type AS \"CONSTRAINT_TYPE\",
                tc.constraint_name AS \"CONSTRAINT_NAME\",
                kcu.ordinal_position AS  \"ORDINAL_POSITION\",
                kcu.column_name AS \"COLUMN_NAME\"
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
            ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
            WHERE tc.table_name = '$table' AND (tc.table_schema = 'public' OR tc.table_schema = '$msc->db')
            ORDER BY kcu.ordinal_position; ";
        return $msc->getData($sql, \PDO::FETCH_OBJ);
    }

    public function selectDb(string $db): void
    {
        global $msc;
        if ($db != $msc->db) {
            $config = $msc->getConfig();
            $msc->connectPdo($config, $db);
        }
    }

    public function sqlCreateTable(string $table): string
    {
        return '';
    }

    /**
     * @param string $table
     * @return array<array<string>>
     * @throws \Exception
     */
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
     * @return array<array<string>>
     */
    public function getCharsets(): array
    {
        global $msc;
        //$data = $msc->getData('SHOW SERVER_ENCODING;');
        //$data = $msc->getData('SHOW CLIENT_ENCODING;');
        //return $msc->getData('SELECT character_set_name as "Charset" FROM information_schema.character_sets;');
        return [];
    }

    /**
     * @return array<array<string>>
     * @throws \Exception
     */
    public function getProcessList(): array
    {
        global $msc;
        return $msc->getData('SELECT * FROM pg_stat_activity');
    }

    /**
     * @param string $table
     * @return TableInfo
     * @throws \Exception
     */
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

    /**
     * @param string $table
     * @return string
     * @throws \Exception
     */
    private function getComment(string $table): string
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
