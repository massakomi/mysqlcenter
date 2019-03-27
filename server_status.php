<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
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


$msc->pageTitle = 'Статус сервера';

$kill = GET('kill');
// Kills a selected process
if (!empty($kill)) {
	if ($msc->query('KILL ' . $kill)) {
		$message = sprintf($strThreadSuccessfullyKilled, $kill);
	} else {
		$message = sprintf($strCouldNotKill, $kill);
	}
}


echo '<b>Список процессов</b>';
// Sends the query and buffers the result
$serverProcesses = array();
$sql_query = 'SHOW FULL PROCESSLIST';
$res = $msc->query($sql_query);
while ($row = mysqli_fetch_assoc($res)) {
	 $serverProcesses[] = $row;
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
echo $table -> make();


/**
 * Sends the query and buffers the result
 */
$res = $msc->query('SHOW STATUS');
while ($row = mysqli_fetch_array($res)) {
  $serverStatus[$row[0]] = $row[1];
}
unset($res, $row);


/**
 * Displays the page
 */
//Uptime calculation
$res = $msc->query('SELECT UNIX_TIMESTAMP() - ' . $serverStatus['Uptime']);
$row = mysqli_fetch_array($res);
echo 'ServerStatusUptime - ' . date(MS_DATE_FORMAT, $row[0]);
unset($res, $row);

//Get query statistics
$queryStats = array();
$tmp_array = $serverStatus;
foreach ($tmp_array AS $name => $value) {
  if (substr($name, 0, 4) == 'Com_') {
    $queryStats[str_replace('_', ' ', substr($name, 4))] = $value;
    unset($serverStatus[$name]);
  }
}
unset($tmp_array);

?>
<ul>
  <li>
    <!-- Server Traffic -->
    <b>Трафик</b>: Эти таблицы показывают статистику по сетевому трафику MySQL сервера со времени его запуска.<br />
    <table border="0" cellpadding="5" cellspacing="0">
      <tr>
        <td valign="top">
          <table border="0" cellpadding="2" cellspacing="1">
            <tr>
              <th colspan="2">&nbsp;Трафик&nbsp;</th>
              <th>&nbsp;&oslash;&nbsp;В час&nbsp;</th>
            </tr>
            <tr>
              <td>&nbsp;Получено&nbsp;</td>
              <td align="right">&nbsp;<?php echo join(' ', PMA_formatByteDown($serverStatus['Bytes_received'])); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo join(' ', PMA_formatByteDown($serverStatus['Bytes_received'] * 3600 / $serverStatus['Uptime'])); ?>&nbsp;</td>
            </tr>
            <tr>
              <td>&nbsp;ОТправлено&nbsp;</td>
              <td align="right">&nbsp;<?php echo join(' ', PMA_formatByteDown($serverStatus['Bytes_sent'])); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo join(' ', PMA_formatByteDown($serverStatus['Bytes_sent'] * 3600 / $serverStatus['Uptime'])); ?>&nbsp;</td>
            </tr>
            <tr>
              <td>&nbsp;Всего&nbsp;</td>
              <td align="right">&nbsp;<?php echo join(' ', PMA_formatByteDown($serverStatus['Bytes_received'] + $serverStatus['Bytes_sent'])); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo join(' ', PMA_formatByteDown(($serverStatus['Bytes_received'] + $serverStatus['Bytes_sent']) * 3600 / $serverStatus['Uptime'])); ?>&nbsp;</td>
            </tr>
          </table>
        </td>
        <td valign="top">
          <table border="0" cellpadding="2" cellspacing="1">
            <tr>
              <th colspan="2">&nbsp;Соединений&nbsp;</th>
              <th>&nbsp;&oslash;&nbsp;В час&nbsp;</th>
              <th>&nbsp;%&nbsp;</th>
            </tr>
            <tr>
              <td>&nbsp;Неудачных попыток&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format($serverStatus['Aborted_connects'], 0, '.', ','); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format(($serverStatus['Aborted_connects'] * 3600 / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo ($serverStatus['Connections'] > 0 ) ? number_format(($serverStatus['Aborted_connects'] * 100 / $serverStatus['Connections']), 2, '.', ',') . '&nbsp;%' : '---'; ?>&nbsp;</td>
            </tr>
            <tr>
              <td>&nbsp;Отменены&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format($serverStatus['Aborted_clients'], 0, '.', ','); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format(($serverStatus['Aborted_clients'] * 3600 / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo ($serverStatus['Connections'] > 0 ) ? number_format(($serverStatus['Aborted_clients'] * 100 / $serverStatus['Connections']), 2 , '.', ',') . '&nbsp;%' : '---'; ?>&nbsp;</td>
            </tr>
            <tr>
              <td>&nbsp;Всего&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format($serverStatus['Connections'], 0, '.', ','); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format(($serverStatus['Connections'] * 3600 / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format(100, 2, '.', ','); ?>&nbsp;%&nbsp;</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </li>
  <li>
    <!-- Queries -->
    <?php echo sprintf('Стат запросов', number_format($serverStatus['Questions'], 0, '.', ',')) . "\n"; ?>
    <table border="0" cellpadding="5" cellspacing="0">
      <tr>
        <td colspan="2">
          <table border="0" cellpadding="2" cellspacing="1" width="100%">
            <tr>
              <th>&nbsp;Всего&nbsp;</th>
              <th>&nbsp;&oslash;&nbsp;В час&nbsp;</th>
              <th>&nbsp;&oslash;&nbsp;В минуту&nbsp;</th>
              <th>&nbsp;&oslash;&nbsp;В секунду&nbsp;</th>
            </tr>
            <tr>
              <td align="right">&nbsp;<?php echo number_format($serverStatus['Questions'], 0, '.', ','); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format(($serverStatus['Questions'] * 3600 / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format(($serverStatus['Questions'] * 60 / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format(($serverStatus['Questions'] / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td valign="top">
          <table border="0" cellpadding="2" cellspacing="1">
            <tr>
              <th colspan="2">&nbsp;Тип запроса&nbsp;</th>
              <th>&nbsp;&oslash;&nbsp;В час&nbsp;</th>
              <th>&nbsp;%&nbsp;</th>
            </tr>
<?php

$useBgcolorOne = TRUE;
$countRows = 0;
foreach ($queryStats as $name => $value) {

// For the percentage column, use Questions - Connections, because
// the number of connections is not an item of the Query types
// but is included in Questions. Then the total of the percentages is 100.
?>
            <tr>
              <td>&nbsp;<?php echo htmlspecialchars($name); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format($value, 0, '.', ','); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format(($value * 3600 / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo number_format(($value * 100 / ($serverStatus['Questions'] - $serverStatus['Connections'])), 2, '.', ','); ?>&nbsp;%&nbsp;</td>
            </tr>
<?php
  $useBgcolorOne = !$useBgcolorOne;
  if (++$countRows == ceil(count($queryStats) / 2)) {
    $useBgcolorOne = TRUE;
?>
          </table>
        </td>
        <td valign="top">
          <table border="0" cellpadding="2" cellspacing="1">
            <tr>
              <th colspan="2">&nbsp;Тип запроса&nbsp;</th>
              <th>&nbsp;&oslash;&nbsp;В час&nbsp;</th>
              <th>&nbsp;%&nbsp;</th>
            </tr>
<?php
  }
}
unset($countRows);
unset($useBgcolorOne);
?>
          </table>
        </td>
      </tr>
    </table>
  </li>
<?php
//Unset used variables
unset($serverStatus['Aborted_clients']);
unset($serverStatus['Aborted_connects']);
unset($serverStatus['Bytes_received']);
unset($serverStatus['Bytes_sent']);
unset($serverStatus['Connections']);
unset($serverStatus['Questions']);
unset($serverStatus['Uptime']);

if (!empty($serverStatus)) {
?>
  <li>
    <!-- Other status variables -->
    <b>Other status variables</b><br />
    <table border="0" cellpadding="5" cellspacing="0">
      <tr>
        <td valign="top">
          <table border="0" cellpadding="2" cellspacing="1">
            <tr>
              <th>&nbsp;Имя&nbsp;</th>
              <th>&nbsp;Значение&nbsp;</th>
            </tr>
<?php
  $useBgcolorOne = TRUE;
  $countRows = 0;
  foreach ($serverStatus AS $name => $value) {
?>
            <tr>
              <td>&nbsp;<?php echo htmlspecialchars(str_replace('_', ' ', $name)); ?>&nbsp;</td>
              <td align="right">&nbsp;<?php echo htmlspecialchars($value); ?>&nbsp;</td>
            </tr>
<?php
    $useBgcolorOne = !$useBgcolorOne;
    if (++$countRows == ceil(count($serverStatus) / 3) || $countRows == ceil(count($serverStatus) * 2 / 3)) {
      $useBgcolorOne = TRUE;
?>
          </table>
        </td>
        <td valign="top">
          <table border="0" cellpadding="2" cellspacing="1">
            <tr>
              <th>&nbsp;Имя&nbsp;</th>
              <th>&nbsp;Значение&nbsp;</th>
            </tr>
<?php
    }
  }
  unset($useBgcolorOne);
?>
          </table>
        </td>
      </tr>
    </table>
  </li>
<?php
}
$res = $msc->query('SHOW VARIABLES LIKE \'have_innodb\'');
if ($res) {
  $row = mysqli_fetch_array($res);
  if (!empty($row[1]) && $row[1] == 'YES') {
?>
  <br />
  <li>
    <!-- InnoDB Status -->
    <a href="./server_status.php?<?php echo $url_query; ?>&amp;innodbstatus=1">
      <b><?php echo $strInnodbStat; ?></b>
    </a>
  </li>
<?php
  }
} else {
  unset($res);
}
?>
</ul>

<?php



?>