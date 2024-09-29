<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */


/**
 * Formats $value to byte view
 *
 * @param  double   the value to format
 * @param  integer  the sensitiveness
 * @param  integer  the number of decimals to retain
 *
 * @return   array  the formatted value and its unit
 *
 * @access  public
 *
 * @author   staybyte
 * @version  1.2 - 18 July 2002
 */
function PMA_formatByteDown($value, $limes = 6, $comma = 0) {
    $dh       = pow(10, $comma);
    $li       = pow(10, $limes);
    $return_value = $value;
    $unit     = $GLOBALS['byteUnits'][0];
    for ( $d = 6, $ex = 15; $d >= 1; $d--, $ex-=3 ) {
        if (isset($GLOBALS['byteUnits'][$d]) && $value >= $li * pow(10, $ex)) {
            $value = round($value / ( pow(1024, $d) / $dh) ) /$dh;
            $unit = $GLOBALS['byteUnits'][$d];
            break 1;
        } // end if
    } // end for

    if ($unit != $GLOBALS['byteUnits'][0]) {
        $return_value = number_format($value, $comma, '.', ',');
    } else {
        $return_value = number_format($value, 0, '.', ',');
    }
    return array($return_value, $unit);
}

$msc->pageTitle = 'Список процессов';

$kill = GET('kill');
// Kills a selected process
if (!empty($kill)) {
    if ($msc->query($sql = 'KILL ' . $kill)) {
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
$serverProcesses = array();
$sql_query = 'SHOW FULL PROCESSLIST';
$res = $msc->query($sql_query);
while ($row = mysqli_fetch_assoc($res)) {
     $serverProcesses[] = $row;
}

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