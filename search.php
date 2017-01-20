<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

/**
 * Простой поиск для MySQL Center
 * В будущем надо расшириить, сделать поиск по конкретным полям, с условиями
 * + обработка запроса, с окончаниями
 */


/*
SELECT *
FROM `da_files`
WHERE `file_path` LIKE CONVERT( _utf8 '%vote.css%'
USING cp1251 )
COLLATE cp1251_general_ci
LIMIT 0 , 30

*/

// селектор таблиц мульти
classLoad('DatabaseManager');
function getTableMultySelector($extra) {
	global $msc;
	$tableSelectMult = null;
	$listTables = DatabaseManager::getTables();
	foreach ($listTables as $t){
		if ($t == $msc->table || $msc->table == '') {
			$tableSelectMult .= "<option selected='selected'>$t</option>";
		} else {
			$tableSelectMult .= "<option>$t</option>";
		}
	}	
	return '<select'.$extra.'>'.$tableSelectMult.'</select>';
}

if (!defined('DIR_MYSQL')) { 
	exit('Hacking attempt');
}

$array = POST('table');
$query = POST('query');
$queryField = POST('queryField');

// 1. Режим поиска по таблице
if (GET('table') != null) {
	if (POST('search_for') != null) {
		$msc->pageTitle = 'Найти и заменить';
    	$sql = 'UPDATE `'.$msc->table.'` SET '.POST('field').' = REPLACE(`'.POST('field').'`, "'.POST('search_for').'", "'.POST('replace_in').'")';
    	if ($msc->query($sql)) {
    		$c = mysql_affected_rows();
    		if ($c > 0) {
    			return $msc->addMessage('Таблица изменена, затронуто рядов: '.$c, $sql, MS_MSG_SUCCESS);
    		} else {
    			return $msc->addMessage('Ничего не найдено и не заменено', $sql, MS_MSG_NOTICE);
    		}
    	} else {
    		return $msc->addMessage('Ошибка при изменении таблицы', $sql, MS_MSG_FAULT, mysql_error());
    	}
	}
	$fields = getFields(GET('table'), true);
	$fieldSelector = plDrawSelector($fields, ' name="field"', array_search(POST('field'), $fields), '', false) ;
	$msc->pageTitle = 'Поиск по таблице';
	include DIR_MYSQL . 'tpl/searchTable.htm.php';
	echo '<h1>Поиск по базе данных</h1>';
	include(MS_DIR_TPL . 'search.htm.php');
// 2. Режим поиска по БД	
} else {

	$msc->pageTitle = 'Поиск по базе данных';
	
    if (strlen($queryField) > 0) {
		$array = DatabaseManager::getTables();
        $msc->pageTitle = "Поиск - база данных $msc->db";
        
		$t = new Table('contentTable');
		$t -> makeRowHead('Таблица', 'Найдено');
		$t -> setColClass('', 'text-align:right');
		$founded = 0;
		$foundedTotal = 0;
		foreach ($array as $table) {
			$fields = getFields($table, true);
			$founds = array();
			foreach ($fields as $field) {
                if (stristr($field, $queryField)) {
                    $founds []= $field;
                    $foundedTotal ++;
                }
            }
			// найдено что-то
			$DTSquery = MS_URL.'?db='.$msc->db.'&table='.$table.'&s=tbl_data';
			if (count($founds) > 0) {
				$founded ++;
			    $table = "<a href='$DTSquery'><b>$table</b></a>";
				$t -> makeRow(array($table, implode(', ', $founds)), " style='color:black'");
			} else {
			 //$table = "<a href='$DTSquery' style='color:#cccccc'>$table</a>";
                //$t -> makeRow(array($table, '-'), "");
      }
		}
		echo $t -> make();
		
    }
	
	else if (strlen($query) > 0) {
		$msc->pageTitle = "Поиск: '$query'";
		if ($array == null || count($array) == 0) {
			if ($msc->table != null) {
				$array = array($msc->table);
				$msc->pageTitle = "Поиск - таблица $msc->table";
			} else {
				$array = DatabaseManager::getTables();
				$msc->pageTitle = "Поиск - база данных $msc->db";
			}		
		}		
		
		// TODO: определить кодировку таблицы
		//$query = iconv('Windows-1251', 'UTF-8', $query);
		
		//if (count($array) > 1) {
		
		$t = new Table('contentTable');
		$t -> makeRowHead('Таблица', 'Найдено');
		$t -> setColClass('', 'text-align:right');
		$founded = 0;
		foreach ($array as $table) {				
			$fields = getFields($table, true);
			$whereCondition = " WHERE " . implode(' LIKE "%'.$query.'%" OR ', $fields) . ' LIKE "%'.$query.'%"';
			$sql = "SELECT COUNT(*) as c FROM $table $whereCondition";			
			$result = $msc->query($sql);
			// найдено что-то
			if (($row = mysql_fetch_object($result)) !== false) {
				if ($row->c > 0) {
					$founded ++;
					$DTSquery = MS_URL.'?db='.$msc->db.'&table='.$table.'&s=tbl_data';
					$t -> makeRow("<b>$table</b>", "<a href='$DTSquery&query=$query'><b>$row->c</b></a>");
				} else {
					//$t -> makeRow(array($table, $row->c), " style='color:#cccccc'");
				}				
			}
		}
		$msc->pageTitle .= " (найдено <b>$founded</b>)";
		echo $t->make();
	}
	
	// HTML форма
	include(MS_DIR_TPL . 'search.htm.php');	
}
?>