<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

/**
 * Скрипт создание таблицы в БД
 */

if (!defined('DIR_MYSQL')) {
	exit('Hacking attempt');
}

/**
 * Создание полей структуры
 *
 * @param array Массив в виде простых чисел (сколько полей надо создать), либо пустое значение, если из POST
 */
function MSC_DrawFields($array='') {
    global $msc;
    
    // получение массива из post
    if (empty($array)) {
       	$array = array();
       	foreach ($_POST['name'] as $key => $name) {
            if (empty($name)) {
                continue;
            }
            $object = new A;
            $object->Field   = $name;
            $object->Type    = strtolower($_POST['ftype'][$key]);
            if ($_POST['length'][$key] > 0) {
                $object->Type .= '('. $_POST['length'][$key].')';
            }
            if ($_POST['attr'][$key] == 'UNSIGNED ZEROFILL') {
                $object->Type .= ' UNSIGNED ZEROFILL';
            } else if ($_POST['attr'][$key] == 'UNSIGNED') {
                $object->Type .= ' UNSIGNED';
            }
            $object->Null    = isset($_POST['isNull'][$key]) ? 'YES' : '';
            if (isset($_POST['uni'][$key])) {
                $object->Key = 'UNI';
            }
            if (isset($_POST['mul'][$key])) {
                $object->Key = 'MUL';
            }
            $object->Key = $_POST['primaryKey'] == $name ? 'PRI' : '';
            $object->Default = $_POST['default'][$key];
            $object->Extra = '';
            if (isset($_POST['auto'][$key])) {
                $object->Extra   = 'AUTO_INCREMENT';
            }
            $array []= $object;
        }
    }

    $keys = getTableKeys($msc->table);

    // получение массива "предыдущих полей" полей
    $fields = array('' => 'FIRST');
    $a = getFields($msc->table, true);
    $previousFields = array();
    $prev = '';
    foreach ($a as $field) {
        $previousFields [$field]= $prev;
        $prev = $field;
        $fields [$field]= $field;
    }
    unset($a, $prev, $field);

    // создание селектора типов данных
    $columnTypes = array(
        'VARCHAR', 'TINYINT', 'TEXT', 'DATE',
        'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT',
        'FLOAT', 'DOUBLE', 'DECIMAL',
        'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR',
        'CHAR', 'TINYBLOB', 'TINYTEXT', 'BLOB', 'MEDIUMBLOB', 'MEDIUMTEXT', 'LONGBLOB', 'LONGTEXT',
        'ENUM', 'SET', 'BOOLEAN', 'SERIAL'
    );
    $types_select = draw_array_options($columnTypes);
    
    $table = new Table('', 0, 4, 0, '', "tableFormEdit");
    $table->makeRowHead(
        'Поле','Тип','Длина/значения','Ноль','По умолчанию',
        '<span title="Автоинкремент">Au</span>',
        '<span title="Primary key">PR</span>',
        '<span title="Unique">UN</span>',
        '<span title="Index">IND</span>',
        '-',
        '<span title="Fulltext">FU</span>',
        'Атрибуты','После...'
    );
    foreach ($array as $k => $v) {
    
        $NAME = $TYPE = $LENGTH = $DEFAULT = $ISNULL = $AUT = $extra = '';
        $isUnsignedZero = $isUnsigned = false;
        $PRI = $UNI = $MUL = $uniName = $mulName = '';

        // уже заполненные поля
        if (is_object($v)) {
            if (preg_match('!\((.*)\)!i', $v->Type, $a)) {
                $LENGTH = $a[1];
            }
            $isUnsignedZero = stristr($v->Type, 'unsigned zerofill');
            $isUnsigned     = stristr($v->Type, 'unsigned') && !$isUnsignedZero;
            $NAME    = $v->Field;
            $TYPE    = preg_replace('~\((.*)\).*~i', '', $v->Type);
            $DEFAULT = $v->Default;
            $ISNULL  = $v->Null === true || $v->Null === 'YES'  ? ' checked' : '';
            $AUT     = $v->Extra ? ' checked' : '';
            $extra = plDrawSelector($fields, ' name="after[]"', $previousFields[$v->Field], '', false);
            $extra .= '<input type="hidden" name="afterold[]" value="'.(!empty($previousFields[$v->Field]) ?
                $previousFields[$v->Field] : 'FIRST').'" >';
            // выбор ключей
            if (isset($keys[$NAME])) {
                foreach ($keys[$NAME] as $keyName => $key) {
                    $PRI = ($key == 'PRI' || (isset($_POST['primaryKey']) && $_POST['primaryKey'] == $NAME) ?
                        ' checked' : $PRI);
                    if ($key == 'UNI' || isset($_POST['uni'][$NAME])) {
                        $UNI = ' checked';
                        $uniName = $keyName;
                    }
                    if ($key == 'MUL' || isset($_POST['mul'][$NAME])) {
                        $MUL = ' checked';
                        $mulName = $keyName;
                    }
                }
            }
        
        // пустые поля
        } else {
            if (POST('afterOption') != '') {
                $checked = '';
                if (POST('afterOption') == 'end') {
                    $checked = array_pop(array_keys($fields));
                } else if (POST('afterOption') == 'field') {
                    $checked = POST('afterField');
                }
                $extra = plDrawSelector($fields, ' name="after['.$k.']"', $checked, '', false);
                $extra .= '<input type="hidden" name="afterold['.$k.'] value="'.$checked.'">';
            }
        }

        // создание ряда
        $j = $NAME == '' ? $k : $NAME; // todo везде должны быть только индексы, не путать с именами полей чтобы
        // id в полях нужно, чтобы из веб-теста получить доступ к полям!
        $table->makeRow(array(
        '<input name="name[]" tabindex="1" id="name'.$k.'" type="text" value="'.$NAME.'" size="15" />
            <input type="hidden" name="oldname[]" value="'.$NAME.'" />',
            
        '<select name="ftype[]" tabindex="2" title="'.$TYPE.'" id="typeSelectorId'.$k.'">'.$types_select.'</select>',
        '<input name="length[]" tabindex="3" id="length'.$k.'" type="text" value="'.$LENGTH.'" size="30" />',
        
        '<input name="isNull['.$k.']" tabindex="4" id="isNull'.$k.'" type="checkbox" value="1"'.$ISNULL.' />',
        '<input name="default[]" tabindex="5" id="default'.$k.'" type="text" size="10" value="'.$DEFAULT.'" />',
        
        '<input name="auto['.$k.']" tabindex="6" id="auto'.$k.'" onclick="$(\'default'.$k.'\').value=\'\'"
            type="checkbox" value="1"'.$AUT.' />',

        // primaryKey - значение должно быть либо номером, либо полем, так оно считывается ниже
        '<input name="primaryKey" tabindex="7" id="key1'.$k.'" type="radio" value="'.$j.'"'.$PRI.' />',
        '<input name="uni['.$j.']" tabindex="8" id="key2'.$k.'" type="checkbox" value="'.$uniName.'"'.$UNI.' />',
        '<input name="mul['.$j.']" tabindex="9" id="key3'.$k.'" type="checkbox" value="'.$mulName.'"'.$MUL.' />',
        '<a href="#" onclick="return clearKeys('.$k.')">clear</a>',
        '<input name="fulltext['.$k.']" tabindex="11" id="fulltext'.$k.'" type="checkbox" value="1" />',
        
        '<select name="attr[]" tabindex="12" id="attr'.$k.'" style="width:70px">
            <option></option>
            <option'.($isUnsigned?' selected="selected"':'').'>UNSIGNED</option>
            <option'.($isUnsignedZero?' selected="selected"':'').'>UNSIGNED ZEROFILL</option>
        </select>',
        $extra
        ),
        " id='tableFormEditTr$k'");
    }
    return $table->make();
}

// Получаем начальную инфо о полях таблицы
$fields = getFields($msc->table);

// Получаем массив имён полей из формы.
$names = POST('name');
if (is_array($names) && count($names) > 0 && POST('action') != '') {
    // Ключи
	$uk = isset($_POST['uni']) ? $_POST['uni'] : array();
	$mk = isset($_POST['mul']) ? $_POST['mul'] : array();
	if (POST('action') == 'tableAddEnd' && is_numeric(POST('primaryKey'))) {
        // при добавлении таблицы вместо имён полей у нас только индексы полей, которые создаются в таблице
        // поэтому приходится создавать массивы ключей самостоятельно
        $primaryKey = $names[POST('primaryKey')];
        $uniKeys    = array();
        $mulKeys    = array();
        foreach ($names as $k => $name) {
            if (array_key_exists($k, $uk)) {
                $uniKeys []= $name;
            }
            if (array_key_exists($k, $mk)) {
                $mulKeys []= $name;
            }
        }
    } else {
        $primaryKey = POST('primaryKey');
    	$uniKeys = $uk;
    	$mulKeys = $mk;
    }
	$fieldsDefFull  = array(); // field    definition
	$fieldsDefEdit  = array(); // oldfield definition
	$newKeys        = array();
	foreach ($_POST as $k => $v) {
        $_POST [$k]= POST($k);
	}
	foreach ($names as $k => $name) {
		if (empty($name)) {
			continue;
		}
		$type    = $_POST['ftype'][$k];
		$null    = (isset($_POST['isNull'][$k]));
		$default = $_POST['default'][$k];
		$extra   = (isset($_POST['auto'][$k])) ? 'AUTO_INCREMENT' : null;
		$extra  .= $_POST['attr'][$k] != '' ? ' '.$_POST['attr'][$k] : null;
		$length  = $_POST['length'][$k];
		$define  = getFieldDefinition($type, $null, $default, $extra, $length);
		if (empty($define)) {
            $msc->addMessage('Не удалось создать поле "'.$name.'". Не указаны дополнительные параметры поля',
                '', MS_MSG_FAULT);
            unset($names[$k]);
            continue;
        }
        // after
        $after    = $_POST['after'][$k];
        $afterold = $_POST['afterold'][$k];
        if ($after != $afterold) {
            if ($after == 'FIRST') {
                $define .= ' FIRST';
            } else {
                $define .= ' AFTER `'.$after.'`';
            }
        }
		$fieldsDefFull []= "`$name` $define";
		if (POST('action') == 'fieldsEditEnd') {
			$fieldsDefEdit [$_POST['oldname'][$k]]= '`'.$name .'` '. $define;
		}
		// ключи
        if (array_key_exists($name, $uniKeys)) {
            $newKeys []= "$name UNI ".$uniKeys[$name];
        }
        if (array_key_exists($name, $mulKeys)) {
            $newKeys []= "$name MUL ".$mulKeys[$name];
		}
	}
	// TODO обработка SET ENUM полей
    //echo '<pre>'; print_r($fieldsDefFull); echo '</pre>'; exit;
	/*pre($_POST);
	pre($uniKeys);
	pre($mulKeys);
	exit;*/
	
	// создание запроса на сздание таблицы
	if (POST('action') == 'tableAddEnd') {
		$sql  = "CREATE TABLE `" . POST('table_name') . "` (\r\n  ";
		$sql .= implode(",\r\n  ", $fieldsDefFull);
		if ($primaryKey != '') {
			$sql .= ",\r\n  PRIMARY KEY ($primaryKey)";
		}
		if (count($uniKeys) > 0) {
			$sql .= ",\r\n  UNIQUE (" . implode(', ', $uniKeys) . ")";
		}
		if (count($mulKeys) > 0) {
			$sql .= ",\r\n  INDEX (" . implode(', ', $mulKeys) . ")";
		}
		$sql .= "\r\n)";
		if ($msc->query($sql)) {
			$msc->addMessage('Таблица '.POST('table_name').' создана', $sql, MS_MSG_SUCCESS);
			$msc->table = POST('table_name');
			$msc->pageTitle = 'Обзор таблицы ' . POST('table_name');
			include_once(DIR_MYSQL . 'tbl_data.php');
			return null;
		} else {
			$msc->addMessage('При создании таблицы возникли ошибки '.POST('table_name'), $sql, MS_MSG_NOTICE, mysqli_error());
		}
	}
	// создание запроса на изменение полей
	if (POST('action') == 'fieldsEditEnd') {
		// определение полей
		$a = array();
		foreach ($fieldsDefEdit as $oldFieldName => $def) {
            $oldDefinition = "`$oldFieldName` ".getFieldDefinition($fields[$oldFieldName]);
            //echo "<br />$oldDefinition == $def";exit;
            if ($oldDefinition == $def) {
                continue;
            }
			$a []= ' CHANGE `'.$oldFieldName.'` '. $def;
		}
		$sql  = count($a) == 0 ? '' : 'ALTER TABLE `'.GET('table') . "`\r\n" . implode(",\r\n", $a);
		// ключи		
        $currentKeys = array();
        $a = getTableKeys($msc->table);
        $currentPrimaryKey = '';
		foreach ($a as $fieldName => $currentKeyNames) {
    		foreach ($currentKeyNames as $k => $currentKeyName) {
                if ($currentKeyName != 'PRI') {
                    if (!in_array($fieldName, $names)) {
                        continue;
                    }
                    $currentKeys []= "$fieldName $currentKeyName $k";
                } else {
                    $currentPrimaryKey = $fieldName;
                }
            }
        }
        //pre($newKeys);
        //pre($currentKeys);
        // удаляем пересекающиеся ключи
		foreach ($currentKeys as $currentKey) {
            if (in_array($currentKey, $newKeys)) {
                unset($newKeys[array_search($currentKey, $newKeys)]);
                unset($currentKeys[array_search($currentKey, $currentKeys)]);
            }
        }
        //pre($newKeys);
        //pre($currentKeys);
        $sql2 = array();
		foreach ($currentKeys as $k => $removeKey) {
            list($fieldName, $removeKeyType, $removeKeyName) = explode(' ', $removeKey);
            $sql2 []= 'ALTER TABLE `'.GET('table').'` DROP KEY `'.$removeKeyName.'`';
        }
		foreach ($newKeys as $k => $addKey) {
            list($fieldName, $addKeyType, $addKeyName) = explode(' ', $addKey);
            $sql2 []= 'ALTER TABLE `'.GET('table').'` ADD '.($addKeyType=='UNI'?'UNIQUE':'INDEX').' (`'.$fieldName.'`)';
        }
        if ($currentPrimaryKey != $primaryKey) {
            if ($currentPrimaryKey != '') {
    			if ($primaryKey == '') {
                    $drop = false;
                    // если в числе обновлённых полей нет текущего primary key, то не удалям ключ
                    foreach ($names as $k => $name) {
                        if ($currentPrimaryKey == $name) {
                            $drop = true;
                        }
                    }
                    if ($drop) {
                        dropPrimaryKey(GET('table'));
                    }
                } else {
                    dropPrimaryKey(GET('table'));
                }
			}			
			if ($primaryKey != '') {
                $sql2 []= 'ALTER TABLE `'.GET('table').'` ADD PRIMARY KEY (`'.$primaryKey.'`)';
            }
		}
  /*
        pre($currentKeys);
        pre($newKeys);
        echo "$currentPrimaryKey != $primaryKey";
		pre($sql2);
		exit;*/
		foreach ($sql2 as $s) {
    		if ($msc->query($s)) {
    			$msc->addMessage('Ключи изменены', $s, MS_MSG_SUCCESS);
			} else {
    			$msc->addMessage('Ошибка при изменении ключей', $s, MS_MSG_FAULT, mysqli_error());
			}
		}
		// выполнение
		if ($sql != '') {
    		if ($msc->query($sql)) {
    			$msc->addMessage('Таблица изменена', $sql, MS_MSG_SUCCESS);
    			include DIR_MYSQL . 'tbl_struct.php';
    			return null;
    		} else {
    			$msc->addMessage('Ошибка при изменении таблицы', $sql, MS_MSG_FAULT, mysqli_error());
    		}
        } else {
    		$msc->addMessage('В definition ничего не изменилось', '', MS_MSG_NOTICE);
			include DIR_MYSQL . 'tbl_struct.php';
			return null;
        }
	}
	// создание запроса на добавление
	if (POST('action') == 'fieldsAddEnd') {
		// определение полей
		$a = array();
		foreach ($fieldsDefFull as $def) {
			$a []= ' ADD COLUMN ' . $def . $afterSql;
		}
		$sql  = 'ALTER TABLE `' . GET('table')  . "`\r\n" . implode(",\r\n", $a);
		// ключи
		$oldFields = getFields(GET('table'));
		// выполнение
		if ($msc->query($sql)) {
			include DIR_MYSQL . 'tbl_struct.php';
			$msc->addMessage('Таблица изменена', $sql, MS_MSG_SUCCESS);
			return null;
		} else {
			$msc->addMessage('Ошибка при изменении таблицы', $sql, MS_MSG_FAULT, mysqli_error());
		}
	}
}

// HTML форма
// Создание таблицы
if ($msc->table == null) {
   	$fieldsCount = isset($_POST['name']) ? count($_POST['name']) : POST('numFields', GET('fieldsNum', MS_FIELDS_COUNT));
    if (is_array(POST('name'))) {
    	$cont = MSC_DrawFields();
    } else {
    	$cont = MSC_DrawFields(range(0, $fieldsCount - 1));
    }
	$msc->pageTitle = "Добавить таблицу в базу данных $msc->db";
	include(MS_DIR_TPL . 'tbl_edit.htm.php');
	
// Добавление полей
} else if (POST('action') == 'fieldsAdd') {
	$fieldsCount = isset($_POST['name']) ? count($_POST['name']) : POST('fieldsNum', GET('fieldsNum', MS_FIELDS_COUNT));
  if (is_array(POST('name'))) {
  	$cont = MSC_DrawFields();
  } else {
  	$cont = MSC_DrawFields(range(0, $fieldsCount - 1));
  }
	$msc->pageTitle = 'Добавить поля';
	if (POST('afterOption') == 'start') {
		$afterSql = 'FIRST';
	} else if (POST('afterOption') == 'field') {
		$afterSql = 'AFTER `'.POST('afterField').'`';
	}
	include(MS_DIR_TPL . 'tbl_edit.htm.php');

// Удаление множества полей через POST (удаление одиночных полей через ajax)
} else if (POST('action') == 'fieldsDelete' && isset($_POST['field']) && count($_POST['field']) > 0) {
    // если в таблице осталось только 1 поле, то удаляем таблицу
    if (count($fields) == 1) {
        $sql = 'DROP TABLE `'.$msc->table.'`';
    } else {
        $sql = 'ALTER table `'.$msc->table.'` DROP `' . implode('`, DROP `', $_POST['field']) . '`';
    }
	if ($msc->query($sql)) {
        if (count($fields) == 1) {
            include DIR_MYSQL . 'tbl_list.php';
        } else {
            include DIR_MYSQL . 'tbl_struct.php';
        }
		$msc->addMessage('Таблица изменена', $sql, MS_MSG_SUCCESS);
		return null;
	} else {
		$msc->addMessage('Ошибка при изменении таблицы', $sql, MS_MSG_FAULT, mysqli_error());
	}	

// Редактирование полей или таблицы	
} else {
	// редактируемые поля
	$edited = array();
	if (GET('field') != '') {
		$edited []= stripslashes(urldecode(GET('field')));
	} else if (isset($_POST['field']) && count($_POST['field']) > 0) {
		$edited = $_POST['field'];
	}
    // собираем только те поля, которые реально существуют в таблице
	$array = array();
	foreach ($fields as $row) {
		if (count($edited) > 0) {
			if (in_array($row->Field, $edited)) {
				$array []= $row;
			}
			continue;
		}
		$array []= $row;
	}
	if (count($array) == 0) {
        redirect('?s=tbl_List');
    }
	$cont = MSC_DrawFields($array);
	$msc->pageTitle = 'Редактировать структуру';
	include(MS_DIR_TPL . 'tbl_edit.htm.php');
}
?>
<script language="javascript">
// выбрать в каждом селекторе такое значение, которое равно титлу селектора
var a = document.getElementsByTagName("select");
var founded = false;
for (var i = 0; i < a.length; i ++) {
    if (a[i].id.match(/typeSelectorId/)) {
        t = a[i].title;
        for (var j = 0; j < a[i].options.length; j ++) {
            if (a[i].options[j].text == t.toUpperCase()) {
                a[i].options[j].selected = true;
                founded = true;
            }
        }
    }
}
// Очищает все отмеченные ключи
function clearKeys(k) {
    $('key1'+k).checked = false;
    $('key2'+k).checked = false;
    $('key3'+k).checked = false;
    return false;
}
</script>