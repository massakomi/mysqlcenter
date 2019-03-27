<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */


/**
 * Возвращает оптионсы для селектора кодировок
 *
 * @package
 * @param  string Выбранное значение кодировки
 * @return string HTML
 */
function getCharsetSelector($selected=null) {
   	$charsetList = getCharsetArray(true);
    $charsetSelector = '';
    foreach ($charsetList as $row) {
        $sel = null;
        if ($selected == $row['Charset']) {
            $sel = ' selected="selected"';
        }
        $charsetSelector .= "\n".'<option title="'.$row['Description'].' (default:'.$row['Default collation'].')"'.$sel.'>'.$row['Charset'].'</option>';
    }
    return $charsetSelector;
}

/**
 * Возвращает сравнение выбранной базы данных
 *
 * @param  string  База данных
 * @param  integer Версия MySQL
 * @return string  Сравнение
 */
function getDbCollation($db, $version) {
    global $msc;
    if ($version >= 50000 && $db == 'information_schema') {
        return 'utf8_general_ci';
    }
    if ($version >= 50006) {
        $return = select('SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = \'' . $db . '\' LIMIT 1;', '', 1);
        return $return[0];
    } else if ($version >= 40101) {
        $msc->selectDb($db);
        $return = select('SHOW VARIABLES LIKE \'collation_database\'', 'array', '', 1);
        if ($db !== $msc->db) {
            $msc->selectDb($msc->db);
        }
        return $return[0];
    } else {
        return getServerCollation();
    }
}


/**
 * Возвращает массив баз данных с полной информацией о них
 *
 * @param   string      База данных
 * @param   boolean     Извлечь статистику для MySQL < 5
 * @param   resource    mysql connection
 * @param   string      Сортировка по колонке
 * @param   string      ASC or DESC
 * @param   integer     Старт для LIMIT
 * @param   bool|int    Максимум для LIMIT
 * @return  array       Массив объектов с инфо баз данных
 */
function get_databases_full($database=null, $force_stats=false, $link=null, $sort_by='SCHEMA_NAME', $sort_order='ASC', $limit_offset=0, $limit_count=false) {
    global $msc;
    $sort_order = strtoupper($sort_order);

    if (true === $limit_count) {
        $limit_count = MSC_MAX_DB_LIST;
    }

    // initialize to avoid errors when there are no databases
    $databases = array();

    $apply_limit_and_order_manual = true;

    if (PMA_MYSQL_INT_VERSION >= 50002) {
        $limit = '';
        
        // get table information from information_schema
        if ($database) {
            $sql_where_schema = 'WHERE `SCHEMA_NAME` LIKE \''. addslashes($database) . '\'';
        } else {
            $sql_where_schema = '';
        }

        // for PMA bc:
        // `SCHEMA_FIELD_NAME` AS `SHOW_TABLE_STATUS_FIELD_NAME`
        $sql = '
             SELECT `information_schema`.`SCHEMATA`.*';
        if ($force_stats) {
            $sql .= ',
                    COUNT(`information_schema`.`TABLES`.`TABLE_SCHEMA`)
                        AS `SCHEMA_TABLES`,
                    SUM(`information_schema`.`TABLES`.`TABLE_ROWS`)
                        AS `SCHEMA_TABLE_ROWS`,
                    SUM(`information_schema`.`TABLES`.`DATA_LENGTH`)
                        AS `SCHEMA_DATA_LENGTH`,
                    SUM(`information_schema`.`TABLES`.`MAX_DATA_LENGTH`)
                        AS `SCHEMA_MAX_DATA_LENGTH`,
                    SUM(`information_schema`.`TABLES`.`INDEX_LENGTH`)
                        AS `SCHEMA_INDEX_LENGTH`,
                    SUM(`information_schema`.`TABLES`.`DATA_LENGTH`
                      + `information_schema`.`TABLES`.`INDEX_LENGTH`)
                        AS `SCHEMA_LENGTH`,
                    SUM(`information_schema`.`TABLES`.`DATA_FREE`)
                        AS `SCHEMA_DATA_FREE`';
        }
        $sql .= ' FROM `information_schema`.`SCHEMATA`';
        if ($force_stats) {
            $sql .= '
          LEFT JOIN `information_schema`.`TABLES`
                 ON BINARY `information_schema`.`TABLES`.`TABLE_SCHEMA`
                  = BINARY `information_schema`.`SCHEMATA`.`SCHEMA_NAME`';
        }
        $sql .= '
              ' . $sql_where_schema . '
           GROUP BY BINARY `information_schema`.`SCHEMATA`.`SCHEMA_NAME`
           ORDER BY BINARY `' . $sort_by . '` ' . $sort_order
           . $limit;
        $databases = array();
        $res = $msc->query($sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $databases []= $row;
        }
        unset($sql_where_schema, $sql, $drops);
    } else {
        return array();
    }

    //apply limit and order manually now
    //(caused by older MySQL < 5 or $GLOBALS['cfg']['NaturalOrder'])
    if ($apply_limit_and_order_manual) {

        function _usort_comparison_callback($a, $b) {
            $sorter = 'strnatcasecmp';
            return ($GLOBALS['callback_sort_order'] == 'ASC' ? 1 : -1) * $sorter($a[$GLOBALS['callback_sort_by']], $b[$GLOBALS['callback_sort_by']]);
        }

        $GLOBALS['callback_sort_order'] = $sort_order;
        $GLOBALS['callback_sort_by'] = $sort_by;
        usort($databases, '_usort_comparison_callback');
        unset($GLOBALS['callback_sort_order'], $GLOBALS['callback_sort_by']);

        if ($limit_count) {
            $databases = array_slice($databases, $limit_offset, $limit_count);
        }
    }

    return $databases;
}

/**
 * Возвращает сравнение сервера по умолчанию
 *
 * @return string
 */
function getServerCollation() {
    global $msc, $connection;
    if (is_object($msc)) {
        $r = $msc->query('SHOW VARIABLES LIKE \'collation_server\'');
    } else {
        if ($r = @mysql_query($connection, 'SHOW VARIABLES LIKE \'collation_server\'')) {
            return '';
        }
    }
    $a = array();
    while ($row = mysqli_fetch_array($r)) {
        $a []= $row[1];
    }
    return $a;
}

/**
 * Операции с БД и таблицами
 */
$sort_by = 'SCHEMA_NAME';
if (!empty($_REQUEST['sort_by'])) {
    $sort_by = $_REQUEST['sort_by'];
}
$sort_order = 'asc';
if (isset($_REQUEST['sort_order']) && strtolower($_REQUEST['sort_order']) == 'desc') {
    $sort_order = 'desc';
}

if (!defined('DIR_MYSQL')) {
	exit('Hacking attempt');
}
if ($msc->table == '') {
	$msc->pageTitle = "Действия - БД";
	$DQuery = $umaker->make('db', $msc->db, 's', 'actions');

    $charsetSelector = '';
    $dbInfo = get_databases_full($msc->db, GET('action')=='fullinfo', $connection, $sort_by, $sort_order, 0);
    $dbInfo = $dbInfo[0];
    $dbInfo['collation'] = $dbInfo['DEFAULT_COLLATION_NAME'];
    $charsetSelector = getCharsetSelector(substr($dbInfo['collation'], 0, strpos($dbInfo['collation'], '_')));

	include(MS_DIR_TPL . 'actionsdb.htm.php');
	
} else {
	$msc->pageTitle = "Действия - таблица $msc->table";
	$DTQuery = MS_URL . "?s=$msc->page&db=$msc->db&table=$msc->table";

    $result = $msc->query('SHOW TABLE STATUS FROM '.$msc->db.' LIKE "'.$msc->table.'"');
    $charset = null;
    if ($row = mysqli_fetch_object($result)) {
        $charset = substr($row->Collation, 0, strpos($row->Collation, '_'));
    }
    

    $charsetSelector = getCharsetSelector($charset);
    
	include(MS_DIR_TPL . 'actions.htm.php');
}
?>