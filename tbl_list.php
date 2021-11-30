<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

classLoad('DatabaseManager');

/**
 * Главная страница -  таблица таблиц БД а также удаление / очистка таблиц
 */

if (!defined('DIR_MYSQL')) {
	exit('Hacking attempt');
}//$dbm = new DatabaseManager;



$tables = DatabaseTable::getCashedTablesArray();

if (count($tables) == 0) {
	$msc->addMessage('В базе данных нет таблиц');
}

// Простая таблица
if (GET('action') == 'full') {
    $msc->pageTitle = 'Полные данные таблиц "'.$msc->db.'" ';
	echo $contentMain = MSC_printObjectTable($tables);
	return;

// Исследование структуры
} elseif (GET('action') == 'structure') {
    $msc->pageTitle = 'Структура таблиц базы данных "'.$msc->db.'" ';
    foreach ($tables as $key => $table) {
        $tables [$key]->fields = getFields($table->Name);
        $tables [$key]->data = $msc->getData('SELECT * FROM '.$table->Name.' LIMIT 3');
    }
    return include(MS_DIR_TPL . 'tbl_struct_view.htm.php');

// Полная таблица
} else {
    $msc->pageTitle = 'Список таблиц базы данных "'.$msc->db.'" ';
	// Определение фильтра даты по ПОСТу
	$time = 0;
	if (POST('ds_year') != null) {
		$_POST['ds_month'] = 1 + $_POST['ds_month'];
		$time = strtotime("$_POST[ds_month]/$_POST[ds_day]/$_POST[ds_year] $_POST[ds_hour]:$_POST[ds_minut]:$_POST[ds_second]");
	}
	// Создание таблицы
	$table = new Table('contentTable');
	$table->setInterlaceClass('', 'interlace');
	$table->setColClass(null, 'tbl', null, null, null, null, 'rig', 'rig', null, 'num', null, 'rig');
	$table->makeRowHead('&nbsp;', '<b>Таблица</b>', '&nbsp;', '&nbsp;', '&nbsp;', '&nbsp;', '<b>Рядов</b>',
    '<b>Размер</b>', '<b>Дата обновления</b>', '<b>Ai</b>', 'Engine', 'Cp');
	$action = POST('act');
	foreach ($tables as $key => $o) {
        /*if (isset($_GET['makeMyIsam'])) {
            if ($o->Engine == 'MyISAM') continue;
            echo '<br>'.$o->Name.' '.$o->Rows;
            $msc->query('ALTER TABLE `'.$o->Name.'` ENGINE = MyISAM');
            echo mysqli_error();
        }*/
        if ($_GET['drop']) echo 'DROP TABLE `' . $o->Name . '`;<br />';

        if ($action == 'analyze' || $action == 'check' || $action == 'flush' || $action == 'repair'
            || $action == 'optimize') {
            $sql = strtoupper($action) . ' TABLE `' . $o->Name . '`';
            if ($msc->query($sql)) {
                $msc->addMessage('Запрос выполнен', $sql, MS_MSG_SUCCESS);
            } else {
                $msc->addMessage('Ошибка запроса', $sql, MS_MSG_FAULT);
            }
        }

        // Фильтровка таблиц по дате
        $updateTime = null;
        if ($o->Update_time > 0) {
            $updateTime = strtotime($o->Update_time);
            if ($time > 0 && $updateTime < $time) {
                unset($tables[$key]);
            }
        }
    }

    $sumTable = 0;
    $sumSize  = 0;
    $sumRows  = 0;
    foreach ($tables as $o) {
		// Увеличение счётчика видимых таблиц
		$sumTable ++;
		// Форматирование даты
        $updateTime = null;
        if ($o->Update_time > 0) {
            $updateTime = strtotime($o->Update_time);
			$updateTime = date2rusString(MS_DATE_FORMAT, $updateTime);
			if (strpos($updateTime, 'дня') !== false || strpos($updateTime, 'ера') !== false) {
				$updateTime = "<b>$updateTime</b>";
			}
		}
		// Форматирование названия таблицы
		$valueName =$o->Name;
		if ($o->Rows == 0) {
			$valueName ='<span style="color:#aaa">'.$valueName.'</span>';
		}
		// Определение размера таблицы
		$size = MSC_roundZero(($o->Data_length + $o->Index_length) / 1024, 1);
		$sumSize += $size;
		$sumRows += $o->Rows;
		// Сборка значения рядов
		$msquery = "db=$msc->db&table=$o->Name";
		$idRow = "row$sumTable";
		$idChbx = "table_" . $o->Name;
		$engine = $o->Engine == 'MyISAM' ? '<span style="color:#ccc">MyISAM</span>' : $o->Engine;
		$rowValues = array(
			'<input name="table[]" type="checkbox" value="'.$o->Name.'" id="'.$idChbx.'" class="cb" '.
                'onclick="checkboxer('.$sumTable.', \'#row\');">',
			'<label for="'.$idChbx.'">'.$valueName.'</label>',
			'<a href="'.$umaker->make('table', $o->Name, 's', 'tbl_data').'" title="Обзор таблицы"><img src="'.MS_DIR_IMG.'actions.gif" alt="" /></a>',
			'<a href="'.$umaker->make('table', $o->Name, 's', 'tbl_struct').'" title="Структура таблицы"><img src="'.MS_DIR_IMG.'generate.png" alt="" /></a>',
			'<a href="#" onClick="msQuery(\'tableTruncate\', \''.$msquery.'&id='.$idRow.'-8&id2='.$idRow.'-9\'); return false" title="Очистить таблицу"><img src="'.MS_DIR_IMG.'delete.gif" alt="" /></a>',
			//'<a href="#" onClick="msQuery(\'tableDelete\', \''.$msquery.'&id='.$idRow.'\'); return false" title="Удалить таблицу"><img src="'.MS_DIR_IMG.'close.png" alt="" /></a>',
            '<img src="'.MS_DIR_IMG.'close.png" data-action="tableDelete" title="Удалить таблицу" alt="" />',
			$o->Rows,
			$size,
			$updateTime,
			$o->Auto_increment,
			'<span>'.$engine.'</span>',
			'<span title="'.$o->Collation.'" style="color:#aaa">'.substr($o->Collation, 0, strpos($o->Collation, '_')).'</span>'
		);
		// Сборка атрибуты рядов
		$rowAttr = array();
		for ($i = 0; $i < 10; $i ++) {
			$rowAttr []= ' id="'.$idRow.'-'.$i.'"';
		}
		$table->makeRow($rowValues,	' style="white-space:nowrap" id="'.$idRow.'"',	false, $rowAttr);
	}
	// Подсчёт размера таблиц
	if ($sumSize > 1024) {
		$sumSize = round($sumSize / 1024, 2) . " мб";
	} else {
		$sumSize = "$sumSize кб";
	}
	$table->makeRow(
		'&nbsp;',
		"$sumTable таблиц",
		'&nbsp;',
		'&nbsp;',
		//'&nbsp;',
		//'&nbsp;',
		'&nbsp;',
		'&nbsp;',
		$sumRows,
		$sumSize,
		'&nbsp;',
		'&nbsp;',
		'&nbsp;',
		'&nbsp;'
	);
	$contentMain = $table->make();
}

include(MS_DIR_TPL . 'tbl_list.htm.php');
