<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
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
$tables = POST('table');
if (count($tables) < 1) {
    $msc->addMessage('Вы не выбрали таблиц для сравнения');
    return null;
}
// Получение массив баз данных
if (isset($_POST['databases'])) {
    $databases = explode(',', $_POST['databases']);
} else {
    $databases = array($_POST['database'], $msc->db);
}

/**
 * Обработка массива/объекта $row для отображения в таблице
 * Конвертация в массив
 */
function processValues($row, $fields, $process = true)
{
    $a = array();
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


function tableCompare($databases, $table)
{
    global $msc;
    $msc->selectDb($databases[0]);
    // Поля таблицы
    $fields = getFields($table);
    $pk = array();
    $fieldsNames = array();
    foreach ($fields as $k => $v) {
        $fieldsNames [] = $v->Field;
        if (strchr($v->Key, 'PRI')) {
            $pk [] = $v->Field;
        }
    }
    // Заплатка для object_parameters - замена  первого ключа на второй
    if (isset($pk[1])) {
        $pk[0] = $pk[1];
    }
    // Порядок
    $orderBy = null;
    if (count($pk) > 0) {
        $orderBy = ' ORDER BY ' . implode(',', $pk); // . ' DESC';
    }
    // Первая  БД
    $sql = "SELECT * FROM $databases[0].$table";
    $result = $msc->query($sql . $orderBy);
    $data = array();
    while ($row = mysqli_fetch_object($result)) {
        $data [$row->$pk[0]][$databases[0]] = processValues($row, $fields, false);
        $data [$row->$pk[0]][$databases[1]] = '-';
    }

    // Вторая БД + сравнение
    $sql = "SELECT * FROM $databases[1].$table";
    $result = $msc->query($sql . $orderBy);
    while ($row = mysqli_fetch_object($result)) {
        $a = processValues($row, $fields, false);
        // Одинаковые ключевые поля
        if (isset($data[$row->$pk[0]][$databases[0]])) {
            // Одиноаквоые ряды
            if ($data[$row->$pk[0]][$databases[0]] == $a) {
                unset($data[$row->$pk[0]][$databases[0]]);
                $data[$row->$pk[0]] = $a;
                // Разные ряды
            } else {
                $data [$row->$pk[0]][$databases[1]] = $a;
            }
            // Новый ряд
        } else {
            $data [$row->$pk[0]][$databases[0]] = '-';
            $data [$row->$pk[0]][$databases[1]] = $a;
        }
    }
    ksort($data);

    // Создание таблицы
    $headers = getTableHeaders($fields);
    $headers = array_merge(array($databases[0], $databases[1]), $headers);
    $tableObject = new Table('contentTable');
    $tableObject->setTableTitle('Таблица: ' . $table);
    $tableObject->makeRowHead($headers);
    foreach ($data as $key => $row) {
        $values = array('+', '+');
        $class = '';
        $attributes = array();

        /*if ($fields[$i]->Type == 'text') {
            $row[$databases[0]] = $row[$databases[1]] = '';
        }*/
        // Разные или новые
        if (isset($row[$databases[0]])) {
            if ($row[$databases[0]] == '-') {
                $values = array('-', '+');
                $attributes = array(' class="n"', ' class="e"');
                $row = processValues($row[$databases[1]], $fields);
            } elseif ($row[$databases[1]] == '-') {
                $values = array('+', '-');
                $attributes = array(' class="e"', ' class="n"');
                $row = processValues($row[$databases[0]], $fields);
                // Есть в обеих таблицах, но разные
            } else {
                $class = 'background-color:#f66';
                $a = array();
                $i = 0;
                foreach ($row[$databases[0]] as $k1 => $v1) {
                    $type = $fields[$i]->Type;
                    $v1 = processRowValue($v1, $type);
                    $v2 = processRowValue($row[$databases[1]][$k1], $type);
                    $a [$i] = $v1 . '<br>' . $v2;
                    $i++;
                }
                $row = $a;
            }
            // Одинаковые
        } else {
            $class = 'background-color:#6f6';
            $row = processValues($row, $fields);
        }
        $tableObject->makeRow(array_merge($values, $row), ' style="' . $class . '"', false, $attributes);
        if ($key > 0 && $key % 30 == 0) {
            //$tableObject->rowsCont .= $tableObject->buildRow($headers, null, true);
        }
    }
    return $tableObject->make();
}


?>

<?php
foreach ($tables as $k => $table) {
    echo tableCompare($databases, $table);
}
