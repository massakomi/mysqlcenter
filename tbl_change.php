<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

if (!defined('DIR_MYSQL')) { 
	exit('Hacking attempt');
}
if ($msc->table == '') {
	exit('Не указана таблица в запросе');
}

require_once DIR_MYSQL . 'includes/tbl_change.inc.php';


/**
 * Вставка рядов
 */
if (GET('row') == '' && POST('row') == '') {

$msc->pageTitle = 'Добавить строки в таблицу';
$head = array();
$fields = getFields($msc->table);
$contentMain = '<table id="tableDataAdd">';
for ($i = 0; $i < MS_ROWS_INSERT; $i ++) {
  $table = new Table();
  // Внимание в таблице SPAN должен быть один, иначе надо менять код JS ниже
	$table -> makeRow(array('Поле', 'Ноль', 'Ряд #<span>'.($i + 1).'</span>', 'Функция'), ' class="editHeader"');
	$j = 0;
	foreach ($fields as $k => $row) {
		addRow($table, $row->Field, $row->Default, $i, $j, $fields);
        $j ++;
	}
	$contentMain .= '<tr><td class="inner">'.$table->make().'</td></tr>';
}

$contentMain .= '</table>';

?>


<form action="" method="post" name="rowsForm" id="rowsForm" class="tableFormEdit">
	<input type="hidden" name="action" value="rowsAdd" />
    <img src="<?php echo MS_DIR_IMG?>nolines_plus.gif" alt="" border="0" onClick="addDataRow('tableDataAdd'); return false" title="Добавить поле" style="cursor:pointer " />
    <img src="<?php echo MS_DIR_IMG?>nolines_minus.gif" alt="" border="0" onClick="removeRow('tableDataAdd', 'end'); return false"  title="Удалить поле" style="cursor:pointer "/><br />
	<?php echo $contentMain ?>
	<br />
	после вставки 
	<input name="a" type="radio" id="f2" onclick="changeCurrentPage(this)" value="tbl_data" checked="checked" /> <label for="f2">обзор таблицы</label>
	<input name="a" type="radio" id="f3" onclick="changeCurrentPage(this)" value="tbl_list" /> <label for="f3">список таблиц</label>
	<input name="a" type="radio" id="f4" onclick="changeCurrentPage(this)" value="tbl_change" /> <label for="f4">вставить новую запись</label>
	<br />
	<input tabindex="100" type="submit" value="Вставить данные!"/>
</form>


<script language="javascript">

/**
 * Специальная функция для изменения параметров скопированного ряда. Сначала копируется ряд.
 * Затем у инпутов с именем row[N][N] и isNull[N][N] меняется номер в первой скобке
 * Затем у спанов (пока там один спан) прибавляется значение
 */
function addDataRow(id) {
	var newTR = addRow(id);
	var inputs = newTR.getElementsByTagName('INPUT')
	for (var i = 0; i < inputs.length; i++) {
        var res = /^row\[(\d+)\]\[(\d+)\]/.exec(inputs[i].name)
        if (res != null) {
            var nextIndexI = Number(res[1]) + 1;
            inputs[i].name = 'row['+nextIndexI+']['+Number(res[2])+']';
        }
        var res = /^isNull\[(\d+)\]\[(\d+)\]/.exec(inputs[i].name)
        if (res != null) {
            var nextIndexI = Number(res[1]) + 1;
            inputs[i].name = 'isNull['+nextIndexI+']['+Number(res[2])+']';
        }
    }
    var spans = newTR.getElementsByTagName('SPAN');
	for (var i = 0; i < spans.length; i++) {
        spans[i].innerHTML = Number(spans[i].innerHTML) + 1;
    }
    refreshActions();
}

</script>
<?php
}


/**
 * Изменение рядов
 */
else {

$msc->pageTitle = 'Редактировать данные';


// если в запросе есть ряд (и таблица), то редактируем этот ряд
$whereCondition = null;
$array = POST('row');
if (GET('row') != '') {
	$whereCondition = urldecode(stripslashes(GET('row')));
// массовый едит
}	else if (!is_null($array) && count($array) > 0) {
	$_POST['row'] = array_map('urldecode', array_map('stripslashes', $_POST['row']));
	$whereCondition = implode(' OR ', $_POST['row']);
}
	
// создания таблицы для данных
$contentMain = null;
if ($whereCondition != null) {
	$result = $msc->query('SELECT * FROM '.$msc->table.' WHERE '.$whereCondition);
	if (!$result) {
		$msc->notice('Ничего не выбрано');
		return;
	}
	$fields = getFields($msc->table);
	$fieldsNames = array();
	foreach ($fields as $v) {
		$fieldsNames []= $v->Field;
	}		
	$j = 0;
    $table = new Table();
    $contentMain = '';
	while (($data = mysql_fetch_object($result)) !== false) {
		$i = 0;
		$table->makeRow(array('Поле', 'Ноль', 'Ряд #' . ($j + 1), 'Функция'), ' class="editHeader"', '');
		$pk = array();
		foreach ($data as $name => $value) {
			$key  = $fields[$name]->Key;
			if (strchr($key, 'PRI')) {
				$pk []= $name.'="'.$value.'"';
			}
			addRow($table, $name, $value, $j, $i, $fields);
			$i ++;
		}
		if (count($pk) == 0) {			
			foreach ($fieldsNames as $v) {
				if ($data->$v == null) {
					continue;
				}
				$pk []= $v.'="'.$data->$v.'"';
			}
		}
		// последний ряд - 
		$cond = urlencode(implode(' AND ', $pk));
		$contentMain .= '<input name="cond[]" type="hidden" value="'.$cond.'">';
		$j ++;
	}
	$contentMain .= $table->make();
}

// выводим форму только если непустой контент
if ($contentMain != null) {
	
?>
<form method="post" action="" name="rowsForm" class="tableFormEdit" id="rowsForm">
<input type="hidden" name="action" value="rowsEdit">
<?php echo $contentMain?>

<label for="f3"><input name="option" type="radio" value="save" id="f3" checked /> сохранить</label>	<br>
или <br />
<label for="f4"><input name="option" type="radio" value="insert" id="f4" /> вставить новый ряд</label>	<br>

<br />
после вставки 
<input name="a" type="radio" id="f2" onclick="changeCurrentPage(this)" value="tbl_data" checked="checked" /> <label for="f2">обзор таблицы</label>
<input name="a" type="radio" id="f3" onclick="changeCurrentPage(this)" value="tbl_list" /> <label for="f3">список таблиц</label>
<input name="a" type="radio" id="f4" onclick="changeCurrentPage(this)" value="tbl_change" /> <label for="f4">вставить новую запись</label>

<input tabindex="100" type="submit" value="Вставить данные!" class="submit" />
</form>
<?php 
}
}
?>
<script language="javascript">

function changeCurrentPage(obj) {
	$('rowsForm').action = '?s='+obj.value+'&table=<?php echo $msc->table ?>&db=<?php echo $msc->db ?>';
}
changeCurrentPage($('f2'));


/**
 * Назначает события функций processNullInput / processNull
 */
function refreshActions() {
    var inputs = $('rowsForm').getElementsByTagName('INPUT')
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].type == 'checkbox') {
            var idName = inputs[i].name.substr(6);
            var chbxFieldName = inputs[i].name;
            var textFieldName = 'row' + idName;
            list($('rowsForm')[textFieldName], 'keyup', function () {
                var chbxFieldName = 'isNull' + this.name.substr(3);
                processNullInput($('rowsForm')[chbxFieldName], this)
            });
            list($('rowsForm')[chbxFieldName], 'click', function () {
                var textFieldName = 'row' + this.name.substr(6);
                processNull(this, $('rowsForm')[textFieldName])
            });
        }
    }
}
refreshActions()

// Если чекбокс отмечен, то значение текстового поля обнуляется
function processNull(checkbox, textinput) {
	if (checkbox.checked) {
		textinput.value = '';
	}
}
// Если значение текстового поля оказывается пустым, то чекбокс отмечается
function processNullInput(checkbox, textinput) {
	if (textinput.value != '') {
		checkbox.checked = false;
	}
}
</script>