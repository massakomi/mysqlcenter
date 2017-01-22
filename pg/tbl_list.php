<?php

$tables = listTablesFull();

$rows = '';
foreach ($tables as $k => $v) {
    $table = $v['relname'];
	$rows .= '
    <tr>
        <td><a href="?table='.$table.'">'.$table.'</a></td>
        <td>'. $v['reltuples'].'</td>
        <td><a href="#">Удалить</a></td>
    </tr>';
}
?>

<h3>База данных <?=DB_NAME?></h3>

<div class="row">
    <div class="col-md-4">
        <table class="table table-pg"><?=$rows?></table>
    </div>
    <div class="col-md-4">

    </div>
    <div class="col-md-4">

    </div>
</div>
