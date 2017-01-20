<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

list($vi, $vs) = getServerVersion();
$msc->pageTitle = 'Переменные сервера ('.$vi.')';

/**
 * Sends the queries and buffers the results
 */
if ($vi >= 40003) {
	$res = $msc->query('SHOW SESSION VARIABLES');
	while ($row = mysql_fetch_array($res)) {
		$serverVars[$row[0]] = $row[1];
	}
	unset($res, $row);
	$res = $msc->query('SHOW GLOBAL VARIABLES');
	while ($row = mysql_fetch_array($res)) {
		$serverVarsGlobal[$row[0]] = $row[1];
	}
	unset($res, $row);
} else {
	$res = $msc->query('SHOW VARIABLES');
	while ($row = mysql_fetch_array($res)) {
			$serverVars[$row[0]] = $row[1];
	}
	unset($res, $row);
}


/**
 * Displays the page
 */
$table = new Table('contentTable');
$table->makeRowHead('Переменная', 'Session значение', 'Global значение');
foreach ($serverVars as $name => $value) {
    $style = ' style="color:#ccc"';
    if ($value != $serverVarsGlobal[$name]) {
        $style = '';
    }
	$table->makeRow(
	'<b>'.htmlspecialchars(str_replace('_', ' ', $name)).'</b>',
	htmlspecialchars($value),
	'<span'.$style.'>'.htmlspecialchars($serverVarsGlobal[$name]).'</span>'
	);
}
echo $table->make();
?>