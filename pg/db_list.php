<?php

function listUsers()
{
    $data = getData('select * from pg_shadow');
    $users = array();
    foreach ($data as $k => $v) {
    	$users [$v['usesysid']]= $v['usename'];
    }
    return $users;
}

// dropdb test

$users = listUsers();
$data = listDatabases();

$rows = '';
foreach ($data as $k => $v) {
    $db = $v['datname'];
    $size = getVal('SELECT pg_database_size(\''.$db.'\')');
    $enc = $v['encoding'];
    if ($enc == 6) {
    	$enc = 'UTF8';
    }
	$rows .= '
    <tr>
        <td><a href="?db='.$db.'">'.$db.'</a></td>
        <td>'.$users[$v['datdba']].'</td>
        <td>'.$enc.'</td>
        <td>'.$v['datcollate'].'</td>
        <td>'.$v['datctype'].'</td>
        <td>'.formatSize($size).'</td>
    </tr>';
}
?>

<h3>Базы данных</h3>

<?php
if (!DB_NAME) {
    err('Нужно выбрать базу данных');
}
?>

<table class="table table-pg">
    <tr>
        <th>База данных</th>
        <th>Пользователь</th>
        <th>Кодировка</th>
        <th>Collation </th>
        <th>Character Type</th>
        <th>Размер</th>
    </tr>
    <?=$rows?>
</table>

<h3>Активные запросы</h3>

<?php
printTable(getData('select * from pg_stat_activity'));
?>


<h3>Статистика баз</h3>
<?php
printTable(getData('select * from pg_stat_database'))
?>