<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

include_once(DIR_MYSQL . 'includes/Export.class.php');

/**
 * Сравнение баз данных
 */

if (!defined('DIR_MYSQL')) { 
    exit('Hacking attempt');
}
$msc->pageTitle = 'Сравнение баз данных';

// Проверка
$databases = POST('databases');
if (count($databases) < 2) {
    $msc->addMessage('Вы не выбрали базы данных для сравнения');
    return null;
}

// Создание начальных массивов
$dbArray = array();
$dbSimpleArray = array();
foreach ($databases as $k => $v) {
    $result = $msc->query('SHOW TABLE STATUS FROM '.$v);
    $dbArray[$k] = array();
    $dbSimpleArray[$v] = array();
    while ($row = mysqli_fetch_object($result)) {
        $dbArray[$v][$row->Name]= $row;
        $dbSimpleArray[$v][]= $row->Name;
    }
}
// Созание полного списка таблиц двух БД
$tablesArray = array();
foreach ($dbSimpleArray as $v) {
  $tablesArray = array_merge($tablesArray, $v);
}
$tablesArray = array_unique($tablesArray);
asort($tablesArray);

// ГЛАВНАЯ ТАБЛИЦА

// Шапка таблицы
$params = array('Есть?', 'Рядов', 'Размер', 'Стр-ра');
$customHeader = '<tr><td rowspan=2>&nbsp;</td><td rowspan=2>Таблица</td>';
$dbc = count($databases);
foreach ($params as $param) {
    $customHeader .= '
    <td colspan="'.$dbc.'">'.$param.'</td>
    ';
}
$customHeader .= '</tr><tr>';
foreach ($params as $param) {
    foreach ($databases as $v) {
        $customHeader .= '<td>'.$v.'</td>
    ';
    }
}
$customHeader .= '</tr>';

// Старт создания главной таблицы
$export = new MySQLExport();
$export->setComments(0);
$export->setOptionsStruct(0, $addAuto=0, 0);
$exportDifference = array();
$tableObject = new Table('contentTable anone');
$tableObject->setHeadContent($customHeader);
$tableObject->setColClass('', 'font-weight:bold','b','b','r','r','r','r','r','r','r');
foreach ($tablesArray as $tableNum => $table) {
    $row = array();
    $row []= '<input name="table[]" type="checkbox" value="'.$table.'" class="cb" id="t-'.$tableNum.'">';
    $row []= '<label for="t-'.$tableNum.'">'.$table.'</a>';
    $attributes = array('', '');
    $rowStyle = null;
    $skip = false;
    foreach ($params as $num => $param) {
        $countRowsPrev  = null;
        $valueSizePrev  = null;
        $exportDataPrev = null;
        foreach ($dbSimpleArray as $db => $array) {
            $export->data = null;
            $attr = null;
            if ($num == 0) {
                if (in_array($table, $array)) {
                    $row []= '<a href="?db='.$db.'&table='.$table.'&s=tbl_data">есть</a>';
                } else {
                    $row []= '-';
                    $rowStyle = ' class="diff"';
                }
            }
            if ($num == 1) {
                $countRows = isset($dbArray[$db][$table]) ? $dbArray[$db][$table]->Rows : '-';
                if ($countRows === $countRowsPrev) {
                    $attr = ' style="background-color:#ccffcc"';
                    $attributes [count($attributes)-1] = $attr;
                }
                $row []= $countRows;
                $countRowsPrev = $countRows;
            }
            if ($num == 2) {
                if (isset($dbArray[$db][$table])) {
                    $valueData = $dbArray[$db][$table]->Data_length + $dbArray[$db][$table]->Index_length;
                    $valueData = MSC_roundZero($valueData / 1024, 2);
                    if ($valueData === $valueSizePrev) {
                        $attr = ' style="background-color:#99ff99"';
                        $attributes [count($attributes)-1] = $attr;
                        //$skip = true;
                    }
                    $row []= $valueData;
                    $valueSizePrev = $valueData;
                } else {
                    $row []= '-';
                }
            }
            if ($num == 3) {
                if (isset($dbArray[$db][$table])) {
                    $export->setDatabase($db);
                    $export->setTable($table);
                    $exportData = $export->exportStructure(0,0);
                    $exportData = str_replace(' PACK_KEYS=0', '', $exportData);
                    $exportData = preg_replace('~COMMENT=".*"~U', '', $exportData);
                    if ($exportData == $exportDataPrev) {
                        $attr = ' style="background-color:#66ff66"';
                        $attributes [count($attributes)-1] = $attr;
                        //$skip = true;
                    } elseif ($exportDataPrev != null) {
                        $exportDifference[$table] = array($exportDataPrev, $exportData);
                    }
                    $exportDataPrev = $exportData;
                    $row []= '+';
                } else {
                    $row []= '-';
                }
            }
            $attributes [] = $attr;
        }
    }
    if ($skip) {
        continue;
    }
    $tableObject->makeRow($row, $rowStyle, false, $attributes);
}

?>

<form method="post" action="?s=tbl_compare">
    <input type="hidden" name="tableComparsion" value="1">
    <input type="hidden" name="databases" value="<?php echo implode(',', $databases)?>">
    <input tabindex="100" type="submit" value="Сравнить выбранные" class="submit" />
    <?php echo $tableObject->make()?>
</form>
<?php echo pre($exportDifference)?>