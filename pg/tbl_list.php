<?php

if (!DB_NAME) {
    redirect('?s=db_list');
    return ;
}

if ($_GET['action'] == 'dropTable') {
    $table = $_GET['table'];
	query('drop table '.$table);
}

if ($_GET['action'] == 'truncateTable') {
    $table = $_GET['table'];
	query('truncate table '.$table);
}

if ($_GET['action'] == 'copyTable') {
	query('CREATE TABLE '.$_GET['val'].' AS SELECT * FROM '.$_GET['table'].'');
}


$tables = listTablesFull();

$rows = '';
foreach ($tables as $k => $v) {
    $table = $v['relname'];
	$rows .= '
    <tr>
        <td><a href="?s=tbl_data&table='.$table.'">'.$table.'</a></td>
        <td>'. $v['reltuples'].'</td>
        <td><a href="?table='.$table.'&action=dropTable" class="confirm"><i class="glyphicon glyphicon-remove" title="Удалить"></i></a></td>
        <td><a href="?table='.$table.'&action=truncateTable" class="confirm"><i class="glyphicon glyphicon-trash" title="Очистить"></i></a></td>
        <td><a href="?table='.$table.'&action=copyTable" class="prompt" data-prompt="'.$table.'"><i class="glyphicon glyphicon-plus" title="Копировать"></i></a></td>
    </tr>';
}
?>

<style type="text/css">
.table-pg > tbody > tr > td {vertical-align: middle;}
.table-pg td:first-child {font-size:16px; padding:3px 8px!important;}
.table-pg .glyphicon {font-size:12px;}
</style>

<h3>Список таблиц базы <?=DB_NAME?></h3>

<table class="table table-pg"><?=$rows?></table>

