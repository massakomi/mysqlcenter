<?php

global $umaker, $msc;

/**
 * Возвращает оптионсы для селектора кодировок
 *
 * @param string Выбранное значение кодировки
 * @return string HTML
 * @package
 */
function getCharsetSelector($selected = null)
{
    $charsetList = Server::getCharsetArray(true);
    $charsetSelector = '';
    foreach ($charsetList as $row) {
        $sel = null;
        if ($selected == $row['Charset']) {
            $sel = ' selected="selected"';
        }
        $title = $row['Description'] . ' (default:' . $row['Default collation'] . ')';
        $charsetSelector .= "\n" . '<option title="' . $title . '"' . $sel . '>' . $row['Charset'] . '</option>';
    }
    return $charsetSelector;
}

/**
 * Возвращает массив баз данных с полной информацией о них
 *
 * @param string      База данных
 * @param boolean     Извлечь статистику для MySQL < 5
 * @param resource    mysql connection
 * @param string      Сортировка по колонке
 * @param string      ASC or DESC
 * @param integer     Старт для LIMIT
 * @param bool|int    Максимум для LIMIT
 * @return  array       Массив объектов с инфо баз данных
 */
function get_databases_full(
    $database = null,
    $force_stats = false,
    $sort_by = 'SCHEMA_NAME',
    $sort_order = 'ASC',
    $limit_offset = 0,
    $limit_count = false
): array {
    global $msc;
    $sort_order = strtoupper($sort_order);

    if (true === $limit_count) {
        $limit_count = MSC_MAX_DB_LIST;
    }

    // initialize to avoid errors when there are no databases
    $databases = array();

    $apply_limit_and_order_manual = true;

    $limit = '';

    // get table information from information_schema
    if ($database) {
        $sql_where_schema = 'WHERE `SCHEMA_NAME` LIKE \'' . addslashes($database) . '\'';
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
    $databases = $msc->fetchPdo($sql)->fetchAll();
    unset($sql_where_schema, $sql, $drops);


    //apply limit and order manually now
    //(caused by older MySQL < 5 or $GLOBALS['cfg']['NaturalOrder'])
    if ($apply_limit_and_order_manual) {

        function _usort_comparison_callback($a, $b)
        {
            $sorter = 'strnatcasecmp';
            $by = $GLOBALS['callback_sort_by'];
            return ($GLOBALS['callback_sort_order'] == 'ASC' ? 1 : -1) * $sorter($a[$by], $b[$by]);
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

if (GET('users')) {
    $msc->pageTitle = 'Различная информация';

    $users = $msc->getData('SELECT * FROM mysql.user');
    $grants = $msc->getData('SHOW GRANTS');
    $privileges = $msc->getData('SHOW PRIVILEGES');
    $engines = $msc->getData('SHOW ENGINES');

    $pageProps = [
        'users' => $users,
        'grants' => $grants,
        'privileges' => $privileges,
        'engines' => $engines,
    ];
    if (isajax()) {
        return $pageProps;
    }

    $this->template($pageProps);
} elseif ($msc->table == '') {
    $msc->pageTitle = "Действия - БД";
    $DQuery = $umaker->make('db', $msc->db, 's', 'actions');

    $charsetSelector = '';
    $dbInfo = get_databases_full($msc->db, GET('act') == 'fullinfo', $sort_by, $sort_order, 0);
    $dbInfo = $dbInfo[0];
    $dbInfo['collation'] = $dbInfo['DEFAULT_COLLATION_NAME'];
    $charsetSelector = getCharsetSelector(substr($dbInfo['collation'], 0, strpos($dbInfo['collation'], '_')));
    $charsetList = Server::getCharsetArray();
    $processes = $msc->getData('SHOW FULL PROCESSLIST');

    $pageProps = [
        'db' => $_GET['db'],
        'url' => $DQuery,
        'dbInfo' => $dbInfo,
        'charsets' => $charsetList,
        'processes' => $processes,
    ];
    if (isajax()) {
        return $pageProps;
    }

    $this->template($pageProps);
} else {
    $msc->pageTitle = "Действия - таблица $msc->table";
    $DTQuery = MS_URL . "?s=$msc->page&db=$msc->db&table=$msc->table";

    $result = $msc->fetchPdoObject('SHOW TABLE STATUS FROM ' . $msc->db . ' LIKE "' . $msc->table . '"');
    $charset = null;
    if ($row = $result->fetch()) {
        $charset = substr($row->Collation, 0, strpos($row->Collation, '_'));
    } else {
        $msc->notice('Таблица не найдена');
        return;
    }

    //$charsetSelector = getCharsetSelector($charset);
    $charsetList = Server::getCharsetArray();
    $dbs = Server::getDatabases();

    $pageProps = [
        'url' => $DTQuery,
        'table' => $_GET['table'],
        'db' => $_GET['db'],
        'ai' => $row->Auto_increment ?: '',
        'checksum' => $row->Checksum ?: '',
        'comment' => $row->Comment ?: '',
        'charset' => $row->Collation ? explode('_', $row->Collation)[0] : '',
        'charsets' => $charsetList,
        'dbs' => $dbs,
        'fields' => DatabaseTable::getFields($msc->table, true),
    ];
    if (isajax()) {
        return $pageProps;
    }

    $this->template($pageProps);
}
