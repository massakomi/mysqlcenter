<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

require_once DIR_MYSQL . 'includes/Export.class.php';

/**
 * Сравнение баз данных
 */

if (!defined('DIR_MYSQL')) {
    exit('Hacking attempt');
}
$msc->pageTitle = 'Сравнение таблиц';

// Проверка
$tables = POST('table') ?: [GET('table')];
if (count($tables) < 1) {
    $msc->addMessage('Вы не выбрали таблиц для сравнения');
    return null;
}
// Получение массив баз данных
if (count($_POST)) {
    if (isset($_POST['databases'])) {
        $databases = explode(',', $_POST['databases']);
    } else {
        $databases = [$_POST['database'], $msc->db];
    }
} else {
    $databases = [GET('db'), GET('db2')];
}


/**
 * Обработка массива/объекта $row для отображения в таблице
 * Конвертация в массив
 */
function processValues($row, $fields, $process = true)
{
    $a = [];
    $i = 0;
    foreach ($row as $k => $v) {
        if ($process) {
            $type = $fields[$i]->Type;
            $v = processRowValue($v, $type);
        }
        $a [$k] = $v;
        $i++;
    }
    return $a;
}

function getPrimaryKeys($fields) {
    $pk = [];
    foreach ($fields as $v) {
        if (strchr($v->Key, 'PRI')) {
            $pk [] = $v->Field;
        }
    }
    return $pk;
}

function selectDataFromDatabase($databases, $table, $pk) {
    global $msc;
    $msc->selectDb($databases[0]);
    // Порядок
    $orderBy = null;
    if (count($pk) > 0) {
        $orderBy = ' ORDER BY ' . implode(',', $pk); // . ' DESC';
    }
    // Первая  БД
    $sql = "SELECT * FROM $databases[0].$table";
    $result = $msc->query($sql . $orderBy);
    $data1 = [];
    while ($row = mysqli_fetch_object($result)) {
        $data1 [] = $row;
    }

    // Вторая БД
    $data2 = [];
    $msc->selectDb($databases[1]);
    $sql = "SELECT * FROM $table";
    $result = $msc->query($sql . $orderBy);
    while ($row = mysqli_fetch_object($result)) {
        $data2 [] = $row;
    }

    return [$data1, $data2];
}


$pageProps = [
    'databases' => $databases,
    'tables' => []
];
foreach ($tables as $k => $table) {
    $fields = getFields($table);
    $pk = getPrimaryKeys($fields);
    [$data1, $data2] = selectDataFromDatabase($databases, $table, $pk);
    $tableData = compact('fields', 'pk', 'data1', 'data2');
    $pageProps ['tables'][$table] = $tableData;
}

if (isajax()) {
    return $pageProps;
}

require_once 'tpl/tbl_compare.php';