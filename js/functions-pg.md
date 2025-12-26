```php

function getFields($table, $onlyNames=true)
{
    $data = getData('select * from INFORMATION_SCHEMA.COLUMNS where table_name = \''.$table.'\'');
    if ($onlyNames) {
        $a = array();
        foreach ($data as $k => $v) {
        	$a []= $v['column_name'];
        }
        return $a;
    }
    return $data;
}


function listDatabases()
{
    $data = getData('SELECT * FROM pg_database WHERE datistemplate = false');
    return $data;
}

function listTables($onlyNames=true)
{
    $data = getData('SELECT * FROM information_schema.tables where table_schema=\'public\' ORDER BY table_name;');
    if ($onlyNames) {
    	$a = array();
        foreach ($data as $k => $v) {
        	$a []= $v['table_name'];
        }
        return $a;
    }
    return $data;
}

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
