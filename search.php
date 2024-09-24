<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Простой поиск для MySQL Center
 * В будущем надо расшириить, сделать поиск по конкретным полям, с условиями
 * + обработка запроса, с окончаниями
 */

// селектор таблиц мульти
classLoad('DatabaseManager');
function getTableMultySelector($extra) {
    global $msc;
    $tableSelectMult = null;
    $listTables = DatabaseManager::getTables();
    foreach ($listTables as $t){
        if ($t == $msc->table || $msc->table == '') {
            $tableSelectMult .= "<option selected='selected'>$t</option>";
        } else {
            $tableSelectMult .= "<option>$t</option>";
        }
    }
    return '<select'.$extra.'>'.$tableSelectMult.'</select>';
}

if (!defined('DIR_MYSQL')) {
    exit('Hacking attempt');
}

$array = POST('table');
$query = POST('query');
$queryField = POST('queryField');
$listTables = DatabaseManager::getTables();

if (isajax()) {
    if (GET('db') && !in_array(GET('db'), Server::getDatabases())) {
        ajaxError('База данных не найдена');
    }
    if (GET('table') && !in_array(GET('table'), $listTables)) {
        ajaxError('Таблица не найдена');
    }
}


$pageProps = [
    'query' => POST('query'),
    'queryField' => POST('queryField'),
    'search_for' => POST('search_for'),
    'replace_in' => POST('replace_in'),
    'tables' => $listTables
];

// 1. Режим поиска по таблице
if (GET('table') != null) {
    $pageProps ['fields'] = getFields(GET('table'), true);
    if (isajax()) {
        return $pageProps;
    }
    $msc->pageTitle = 'Поиск по таблице';

    include DIR_MYSQL . 'tpl/searchTable.htm.php';
    echo '<h1>Поиск по базе данных</h1>';

// 2. Режим поиска по БД
} else {

    $msc->pageTitle = 'Поиск по базе данных';

    if (strlen($queryField) > 0) {
        $results = [];
        $founded = 0;
        $foundedTotal = 0;
        foreach ($listTables as $table) {
            $fields = getFields($table, true);
            $founds = [];
            foreach ($fields as $field) {
                if (stristr($field, $queryField)) {
                    $founds []= $field;
                    $foundedTotal ++;
                }
            }
            // найдено что-то
            if (count($founds) > 0) {
                $founded ++;
                $results []= [
                    'table' => ['href' => "/tbl_data/$msc->db/$table", 'text' => $table],
                    'fields' => implode(', ', $founds),
                ];
            }
        }
        return compact('results', 'founded', 'foundedTotal');

    } elseif (strlen($query) > 0) {
        $msc->pageTitle = "Поиск: '$query'";
        if ($array == null || count($array) == 0) {
            if ($msc->table != null) {
                $array = [$msc->table];
                $msc->pageTitle = "Поиск - таблица $msc->table";
            } else {
                $array = DatabaseManager::getTables();
                $msc->pageTitle = "Поиск - база данных $msc->db";
            }
        }

        $results = [];
        $founded = 0;
        foreach ($array as $table) {
            $fields = getFields($table, true);
            $whereCondition = " WHERE " . implode(' LIKE "%'.$query.'%" OR ', $fields) . ' LIKE "%'.$query.'%"';
            $sql = "SELECT COUNT(*) as c FROM $table $whereCondition";
            $result = $msc->query($sql);
            if (!$result) {
                continue;
            }
            // найдено что-то
            if ($row = mysqli_fetch_object($result)) {
                if ($row->c > 0) {
                    $founded ++;
                    $results []= [
                        'table' =>$table,
                        'rows' => [
                            'href' => "/tbl_data/$msc->db/$table/?query=$query",
                            'text' => $row->c
                        ]
                    ];
                }
            }
        }
        $msc->pageTitle .= " (найдено <b>$founded</b>)";
        return compact('results', 'founded');
    }

}

if (isajax()) {
    return $pageProps;
}

// HTML форма
include(MS_DIR_TPL . 'search.htm.php');
