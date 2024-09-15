<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

$msc->pageTitle = 'Различная информация';

if (isajax()) {
    $users = getData('SELECT * FROM mysql.user');
    $grants = getData('SHOW GRANTS');
    $privileges = getData('SHOW PRIVILEGES');
    $engines = getData('SHOW ENGINES');
    return [
        'users' => $users,
        'grants' => $grants,
        'privileges' => $privileges,
        'engines' => $engines,
    ];
}



echo '<h3>Пользователи</h3>';

//


$res = $msc->query('SELECT * FROM mysql.user');
$table = new Table('contentTable');
$data = [];
while ($row = mysqli_fetch_assoc($res)) {
	foreach ($row as $k => $v) {
		if (!isset($data [$k])) {
			$data [$k][]=$k;
		}
		$data [$k][]= $v;
	}
}

foreach ($data as $k => $row) {
	 if ($row[0] == 'User') {
	 	$table->makeRowHead($row);
		continue;
	 }
	 $table->makeRow($row);
}
echo $table -> make();



echo '<h3>SHOW GRANTS</h3>';
$res = $msc->query('SHOW GRANTS');
$table = new Table('contentTable');
while ($row = mysqli_fetch_assoc($res)) {
	 if ($table->tableCont == null) {
	 	$table->makeRowHead(array_keys($row));
	 }
	 $table->makeRow($row);
}
echo 'Список привилегий, предоставленных аккаунту, который вы используете для соединения с сервером (FOR CURRENT_USER)';
echo $table -> make();





echo '<h3>SHOW PRIVILEGES</h3>';
$res = $msc->query('SHOW PRIVILEGES');
$table = new Table('contentTable');
while ($row = mysqli_fetch_assoc($res)) {
	 if ($table->tableCont == null) {
	 	$table->makeRowHead(array_keys($row));
	 }
	 $table->makeRow($row);
}
echo 'Список системных привилегий, которые поддерживает MySQL сервер. Точный список привилегий зависит от версии вашего сервера.';
echo $table -> make();



echo '<h3>SHOW ENGINES</h3>';
$res = $msc->query('SHOW ENGINES');
$table = new Table('contentTable');
while ($row = mysqli_fetch_assoc($res)) {
	 if ($table->tableCont == null) {
	 	$table->makeRowHead(array_keys($row));
	 }
	 $table->makeRow($row);
}
echo 'SHOW ENGINES displays status information about the server\'s storage engines. This is particularly useful for checking whether a storage engine is supported, or to see what the default engine is.';
echo $table -> make();


