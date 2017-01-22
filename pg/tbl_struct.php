<?php

$table = $_GET['table'];

$data = getFieldsFull($table);

// $keys = listKeys();

function printTable($data, $opts=[])
{
    $rows = '';

    $rows .= '<tr>';
    $headers = array_keys($data[0]);
    foreach ($headers as $key => $row) {
        if ($opts) {
        	$row = preg_replace('~(?<=.)_(?=.)~', '<br />', $row);
        }
       	$rows .= '<th>'.$row.'</th>';
    }
    $rows .= '</tr>';

    foreach ($data as $fieldInfo) {
    	$rows .= '<tr>';
        foreach ($fieldInfo as $k => $v) {
            $rows .= '<td>'.$v.'</td>';
        }
        $rows .= '</tr>';
    }
    echo '<table class="table table-pg">'.$rows.'</table>';
}

?>

<h3>Структура таблицы <?=$table?></h3>

<?php

printTable($data, ['cutHeaders' => 1]);
?>