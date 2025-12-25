<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Простой поиск для MySQL Center
 * В будущем надо расшириить, сделать поиск по конкретным полям, с условиями
 * + обработка запроса, с окончаниями
 */

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

    $this->template($pageProps);
    //echo '<h1>Поиск по базе данных</h1>';

// 2. Режим поиска по БД
} else {

    $msc->pageTitle = 'Поиск по базе данных';

    if ($queryField && strlen($queryField) > 0) {
        $results = [];
        $founded = 0;
        $foundedTotal = 0;
        foreach ($listTables as $table) {
            $fields = getFields($table, true);
            $founds = [];
            foreach ($fields as $field) {
                if (preg_match('~[a-z][A-Z]~', $field,)) {
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
        if (isajax()) {
            return compact('results', 'founded', 'foundedTotal');
        } else {
            $msc->pageTitle .= " (найдено <b>$founded</b>)";
            $t = new Table('contentTable');
            $t->makeRowHead('Таблица', 'Найдено');
            $t->setColClass('', 'text-align:right');
            foreach ($results as $row) {
                $table = $row['table']['text'];
                $t->makeRow([
                    "<a href='/?db=$msc->db&table=$table&s=tbl_data'>".$table."</a>",
                    $row['fields'],
                ], " style='color:black'");
            }
            echo $t->make();
        }

    } elseif ($query && strlen($query) > 0) {
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
            $result = $msc->fetchPdoObject($sql);
            if (!$result) {
                continue;
            }
            // найдено что-то
            if ($row = $result->fetch()) {
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
        if (isajax()) {
            return compact('results', 'founded');
        } else {
            $t = new Table('contentTable');
            $t->makeRowHead('Таблица', 'Найдено');
            $t->setColClass('', 'text-align:right');
            foreach ($results as $row) {
                $table = $row['table'];
                $t->makeRow([
                    "<a href='/?db=$msc->db&table=$table&s=tbl_data'>".$table."</a>",
                    $row['rows']['text'],
                ], " style='color:black'");
            }
            echo $t->make();
            echo '<hr />';
        }

    }

    if (isajax()) {
        return $pageProps;
    }

    $this->template($pageProps);

}
