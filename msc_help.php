<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */


if (GET('id') == '') {			
	$result = $msc->query('SELECT * FROM mysql.help_category ORDER BY name');
	$table = new Table('contentTable');
	$table -> setInterlace('', '#eeeeee');
	$headers = [];
	while ($o = mysqli_fetch_object($result)) {
		$data = [];
		if (count($headers) == 0) {
			foreach ($o as $k => $v) {
				$headers []= $k;
			}
			$table->makeRow($headers);
		}
		foreach ($o as $k => $v) {
			if ($k == 'name') {
				$v = "<a href='?s=msc_help&id=$o->help_category_id'>$v</a>";
			}
			$data []= $v;
		}
		$table->makeRow($data);
	}
	echo $table->make();		
	
} else {

	$result = $msc->query('SELECT * FROM mysql.help_topic WHERE help_category_id = ' . GET('id'));
	$table = new Table('contentTable');
	$table -> setInterlace('', '#eeeeee');
	$headers = [];
	while ($o = mysqli_fetch_object($result)) {
		$data = [];
		if (count($headers) == 0) {
			foreach ($o as $k => $v) {
				$headers []= $k;
			}
			$table->makeRow($headers);
		}
		foreach ($o as $k => $v) {
			$data []= $v;
		}
		$table->makeRow($data);
	}
	echo $table->make();		
}		

