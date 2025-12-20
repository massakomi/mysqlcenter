<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

$msc->pageTitle = 'Список процессов';

$kill = GET('kill');
// Kills a selected process
if (!empty($kill)) {
    if ($msc->execPdo($sql = 'KILL ' . $kill)) {
        $msc->addMessage('Успешно удалено');
    } else {
        $msc->addMessage('Ошибка остановки', $sql, MS_MSG_ERROR, $msc->error);
    }
    if (isajax()) {
        return [
                'exec' => 1
        ];
    }
}

// Sends the query and buffers the result
$sql = 'SHOW FULL PROCESSLIST';
$res = $msc->fetchPdo($sql);
$serverProcesses = $res->fetchAll();

if (isajax()) {
    return [
        'serverProcesses' => $serverProcesses
    ];
}

unset($res);
unset($row);
// Displays the page
$table = new Table('contentTable');
$table ->makeRowHead('<a href="&full" title="полные или пустые запросы"><img src="'.MS_DIR_IMG.'s_fulltext.png" width="50" height="20" border="0" alt="" /></a>',	'id',	'user',	'host',	'db',	'command', 'time',	'status',	'sqlQuery');
foreach ($serverProcesses as $name => $value) {
    $table ->makeRow(
    '<a href="'.$_SERVER['REQUEST_URI'].'&kill=' . $value['Id'] . '">Убить</a>',
    $value['Id'],
    $value['User'],
    $value['Host'],
    (empty($value['db']) ? '<i>нет</i>' : $value['db']),
    $value['Command'],
    $value['Time'],
    (empty($value['State']) ? '---' : $value['State']),
    (empty($value['Info']) ? '---' : $value['Info'])
    );
}

echo '<b>Список процессов</b>';
echo $table -> make();