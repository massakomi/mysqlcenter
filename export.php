<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

include_once(DIR_MYSQL . 'includes/Export.class.php');
classLoad('DatabaseManager');
classLoad('MSTable');

/**
 * Экспорт
 */

if (!defined('DIR_MYSQL')) { 
	exit('Hacking attempt');
}

// 1. ИНИЦИАЛИЗАЦИЯ

$msct = new MSTable;

// шапка дампа
$dumpHeader = 
'-- '.MS_APP_NAME.' SQL Экспорт
-- версия '.MS_APP_VERSION.'
--
-- Хост: '.DB_HOST.'
-- Время создания: '.date('j.m.Y, H-i').'
-- Версия сервера: '.PMA_MYSQL_STR_VERSION.'
-- Версия PHP: '.phpversion().'
-- 
-- БД: `'.$msc->db.'`
-- 

-- --------------------------------------------------------
';

// извлечение данных
if (count($_POST) > 0) {
	$isStruct  = (POST('export_struct') != '');
	$isData    = (POST('export_data') != '');
	$exType    = POST('export_option');
	$exWhere   = POST('export_where');	
	$isDrop    = (POST('addDrop') != '');
	$addIfNot  = (POST('addIfNot') != '');
	$addAuto   = (POST('addAuto') != '');
	$addKav    = (POST('addKav') != '');	
	$insFull   = (POST('insFull') != '');
	$insExpand = (POST('insExpand') != '');
	$insZapazd = (POST('insZapazd') != '');
	$insIgnor  = (POST('insIgnor') != '');	
}

// 2. СПЕЦИАЛЬНЫЙ ЭКСПОРТ

if ($msc->page == 'exportSp') {
	$msc->pageTitle = 'Специальный экспорт данных';
	$drawForm = true;
	// Экспорт
	if (POST('exportSpecial') != null && $msc->db != null) {
		// Save
		if (POST('new') != null) {
			if (!$id_set = $msct->insertSet(POST('new'))) {
				return $msc->addMessage('Не смог добавить сет', null, MS_MSG_FAULT, mysqli_error());
			}
			foreach ($_POST['table'] as $key => $t){
				$struct = intval(isset($_POST['struct'][$key]));
				$data   = intval(isset($_POST['data'][$key]));
				$pk_top = intval($_POST['to'][$key]);
				$where_sql = $_POST['where'][$key];
				if ($data) {
					$where = array();
					$pri  = $_POST['field'][$key];
					$from = intval($_POST['from'][$key]);
					$to   = intval($_POST['to'][$key]);
					if ($from < 1) {
						$where []= "$pri >= $from";
					}
					if ($to > 0) {
						$where []= "$pri <= $to";
					}
					if ($where_sql != '') {
						$where []= $where_sql;
					}
					$where_sql = implode(' AND ', $where);
				}
				$msct->insertOption($id_set, $t, $struct, $data, $where_sql, $pk_top);
			}
			$msc->addMessage('Сет добавлен', null, MS_MSG_SUCCESS, mysqli_error());
		// Send
		} else {
			$drawForm = false;
			$exp = new MySQLExport();
			if (POST('addComment') != null) {
				$exp->setHeader($dumpHeader);
			}
			$exp->setDatabase($msc->db);
			$exp->setComments(POST('addComment') != null);
			$exp->setOptionsStruct($addIfNot, $addAuto, $addKav);
			$exp->setOptionsData($insFull, $insExpand, $insZapazd, $insIgnor);
			foreach ($_POST['table'] as $key => $t){
				$exp->setTable($t);
				$whereLocal = stripslashes($_POST['where'][$key]);
				if (isset($_POST['struct'][$key])) {
					$exp->exportStructure($addDelim=1, $isDrop);
				}
				if (isset($_POST['data'][$key])) {
					$where = array();
					$pri  = $_POST['field'][$key];
					$from = intval($_POST['from'][$key]);
					$to   = intval($_POST['to'][$key]);
					if ($from < 1) {
						$where []= "$pri >= $from";
					}
					if ($to > 0) {
						$where []= "$pri <= $to";
					}
					if ($whereLocal != '') {
						$where []= $whereLocal;
					}
					$exp->exportData($exType, implode(' AND ', $where));
				}
			}
			if (intval(POST('export_to')) == 1) {
				echo $exp->send('zip');
			} else {
				echo $exp->send();
            }
		}	
	}
	if ($drawForm) {
	// Отображение формы экспорта
		$cSet = $msct->getSetInfo(GET('set'));
		$setsArray = $msct->getSetsArray();


		$table = new Table('contentTable');
		$table->setInterlace('', '#eeeeee');
		$table->makeRowHead('Таблица', 'Структ', 'Данные', 'Поле', 'Диапазон', 'WHERE');
		$result = $msc->query('SHOW TABLE STATUS FROM '.$msc->db);
		$i = 0;
		while ($o = mysqli_fetch_object($result)) {
			$tprefix = substr($o->Name, 0, strpos($o->Name, '_', $start));
			$checkedStruct = null;
			$checkedData = ' checked';
			$valueMax = null;
			$where = null;
			
			// Определение видимости таблицы
			if (!array_key_exists($o->Name, $cSet)) {
				$checkedData = null;
				//continue;				
			} else {
				// Определение необхдоимости экспорта данных и стр-ры
				if ($cSet[$o->Name]->struct == 1) {
					$checkedStruct = ' checked';
				}
				if ($cSet[$o->Name]->data != 1) {
					$checkedData = null;
				}
				// Определение верхнего значение PK
				$valueMax = $cSet[$o->Name]->pk_top == 0 ? null : $cSet[$o->Name]->pk_top;
				$where    = $cSet[$o->Name]->where_sql;
			}

			// Определение ключевого поля и создание массива полей
			$fields = getFields($o->Name);
			$pKey = '';
			$fnames = array();
			foreach ($fields as $k => $v) {
				$fnames []= $v->Field;
				if (strchr($v->Key, 'PRI') && $pKey == 0) {		
					$pKey = $v->Field;
				}
			}
			if (isset($primaryFields[$o->Name])) {
				$pKey = $primaryFields[$o->Name];
			}			
			// Создание ряда
			$valueTable = $o->Name;
			if ($checkedData == null && $checkedStruct == null) {
				$valueTable = '<span style={{color: \'#aaa\'}}>'.$valueTable.'</span>';
			} else {
				$valueTable = '<b>'.$valueTable.'</b>';
			}			
			$i ++;
			$table->makeRow(array(
				'<label htmlFor="row'.$i.'">'.$valueTable.'</label>',
				'<input name="struct['.$i.']" type="checkbox" value="1" id="row'.$i.'" className="cb"'.$checkedStruct.' /><input name="table['.$i.']" type="hidden" value="'.$o->Name.'" />',
				'<input name="data['.$i.']" type="checkbox" value="1" id="row2'.$i.'" className="cb"'.$checkedData.' />',
				'<select name="field['.$i.']" defaultValue="'.$pKey.'">'.draw_array_options($fnames, '').'</select>',
				'<input name="from['.$i.']" type="text" size="5" defaultValue="1" /> - <input name="to['.$i.']" type="text" size="5" defaultValue="'.$valueMax.'" />',
				'<input name="where['.$i.']" type="text" size="40" defaultValue="'.$where.'" />'
			));
		}

		include(MS_DIR_TPL . 'exportSp.html');
	}
	
// 3. ОБЫЧНЫЙ ЭКСПОРТ

} else {
	$msc->pageTitle = 'Экспорт данных';

	$exportDb = POST('export_db');
	$array = POST('export_table');
    $optionsSelected = [];
	// 3.2.1. Создание
	if (count($array) > 0 || count($exportDb) > 0) {					
		// создание дампа
		$exp = new MySQLExport();
		$exp->setComments(POST('addComment') != '');
		$exp->setHeader($dumpHeader);		
		$exp->setOptionsStruct($addIfNot, $addAuto, $addKav);
		$exp->setOptionsData($insFull, $insExpand, $insZapazd, $insIgnor);			
		// Экспорт БД
		if (count($exportDb) > 0) {				
			foreach ($exportDb as $db) {
				$exp->data .= "\r\n".'CREATE DATABASE `'.$db.'` DEFAULT CHARACTER SET '.MS_CHARACTER_SET.' COLLATE '.MS_COLLATION.';
USE `'.$db.'`;'."\r\n"."\r\n";
				$exp->setDatabase($db);
				$array = DatabaseManager::getTables($db);
				foreach ($array as $t) {
					$exp->setTable($t);
					$exp->startFull($isStruct, $isData, true, $isDrop, $exType, $exWhere);		
				}				
			}
		} 
		// Экспорт таблицы
		else {
			$exp->setDatabase(GET('db'));
			foreach ($array as $t) {
				$exp->setTable($t);
				$exp->startFull($isStruct, $isData, true, $isDrop, $exType, $exWhere);		
			}
		}			
		// Send
		if (intval(POST('export_to')) == 1) {
            $file = $msc->table != '' ? $msc->table : $msc->db;
			echo $exp->send('zip', $file);
        } else {
			echo $exp->send();
        }
	}
	// 3.2.2. HTML форма экспорта
	else {		
		$structChecked = ' defaultChecked';
		$whereCondition = null;
		$tableSelectMult = null;			
		// 3.2.2.1. только если указана в запросе!
		if (GET('db') != '') {
			// массив таблиц из списка таблиц
			$tablesAll = DatabaseManager::getTables();
			$tables = isset($_POST['table']) ? $_POST['table'] : array();
			if (is_null($tables) || count($tables) == 0) {
				if ($msc->table == '') {
                    $optionsSelected = $tablesAll;
				} else {
                    $optionsSelected = array($msc->table);
				}			
			} else {
                $optionsSelected = $tables;
            }
			// массив рядов из обзора таблицы		
			if (POST('rowMulty') != '') {
				$_POST['row'] = array_map('urldecode', array_map('stripslashes', $_POST['row']));
				$whereCondition = '('.implode(') OR (', $_POST['row']).')';
			}			
			// селектор таблиц мульти			
			$selectMultName  = 'export_table[]';
			if ($whereCondition != null) {				
				$structChecked = null;
			}
            $optionsData = $tablesAll;
			/*foreach ($tablesAll as $t){
				if (in_array($t, $tables) || $t == $msc->table) {
					$tableSelectMult .= "<option selected='selected'>$t</option>";
					continue;
				} else {
					$tableSelectMult .= "<option>$t</option>";
				}
			}*/
		}			
		// 3.2.2.2. Если бд не указана, то список БД
		else {
			$selectMultName  = 'export_db[]';
			$dbAll = Server::getDatabases();
            $optionsSelected = POST('databases');
			if ($optionsSelected == '' || count($optionsSelected)== 0) {
                $optionsSelected = array();
			}
			$optionsData = $dbAll;
			/*foreach ($dbAll as $t){
				if (in_array($t, $dbSelected)) {
					$tableSelectMult .= "<option selected>$t</option>";
				} else {
					$tableSelectMult .= "<option>$t</option>";
				}				
			}*/
		}
		include(MS_DIR_TPL . 'export.htm.php');
	}

}
?>