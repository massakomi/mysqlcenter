```php


function listTablesFull()
{
    $tables = listTables();
    $tables = getData('SELECT * FROM pg_class WHERE relname IN (\''.implode('\', \'', $tables).'\') ORDER BY relname');
    return $tables;
}

function primaryKey($table)
{
    $keys = getData('SELECT
    i.relname AS indexname,
    pg_get_indexdef(i.oid) AS indexdef
    FROM pg_index x
    INNER JOIN pg_class i ON i.oid = x.indexrelid
    WHERE x.indrelid = \''.$table.'\'::regclass::oid AND i.relkind = \'i\'::"char"
    AND x.indisprimary');

    preg_match('~\("([^"]+)"\)~i', $keys[0]['indexdef'], $a);

    if (!$a[1]) {
    	$fields = getFields($table);
        if (in_array('id', $fields)) {
            return 'id';
        }
    }

    return $a[1];
}





Разница в выводе ключей
Array
(
    [0] => stdClass Object
        (
            [Table] => db_info
            [Non_unique] => 0
            [Key_name] => PRIMARY
            [Seq_in_index] => 1
            [Column_name] => db_name
            [Collation] => A
            [Cardinality] => 9
            [Sub_part] =>
            [Packed] =>
            [Null] =>
            [Index_type] => BTREE
            [Comment] =>
            [Index_comment] =>
        )

)
Array
(
    [0] => stdClass Object
        (
            [constraint_catalog] => tester
            [constraint_schema] => tester
            [constraint_name] => idx_16996_primary
            [table_catalog] => tester
            [table_schema] => tester
            [table_name] => irc_log
            [constraint_type] => PRIMARY KEY
            [is_deferrable] => NO
            [initially_deferred] => NO
            [enforced] => YES
            [nulls_distinct] =>
            [column_name] => id
            [ordinal_position] => 1
            [position_in_unique_constraint] =>
        )

)