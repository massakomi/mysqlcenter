<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */
 
global $memory_limit;
$memory_limit = (intval(ini_get('memory_limit'))*1024*1024)/2;

/**
 * Библиотека общих функций по экспорту таблиц БД
 */
class MySQLExport {
  var $db, $table, $data, $tableStructure = array();
  var $comments = true;
  var $fields   = array();

  var $addIfNot  = false;
  var $addAuto   = true;
  var $addKav    = true;

  var $insFull   = false;
  var $insExpand = false;
  var $insZapazd = false;
  var $insIgnor  = false;

  /**
   * Позволяет сразу установить опции экспорта
   */
  function MySQLExport($db=null, $table=null, $header=null) {
    $this->table  = $this->tableb = $table;
    $this->db     = $db;
    $this->data   = $header;
  }

  /**
   * Запускает комплексный процесс экспорта
   *
   * $isStruct
   * $isData
   * $addDelim
   * $addDrop
   * $type
   * $where
   */
  function startFull($isStruct=true, $isData=true, $addDelim = true, $addDrop = false, $type = 'INSERT', $where = null) {
    if ($isStruct)
      $this -> exportStructure($addDelim, $addDrop);
    if ($isData)
      $this -> exportData($type, $where);
    return $this->get();
  }

  // Установить текущую базу данных
  function setDatabase($a) {
    if ($this->db != $a) {
      $this->db = $a;
      mysql_select_db($this->db);
    }
  }

  // Установить текущую таблицу
  function setTable($a) {
    $this->table     = $a;
    if ($this->addKav) {
      $this->tableb    = "`$a`";
    } else {
      $this->tableb    = $a;
    }
  }

  // Установить шапку к дампу
  function setHeader($a) {
    if ($this->comments) {
      $this->data     .= $a;
    }
  }

  // Добавлять или нет комментарии
  function setComments($a) {
    $this->comments  = (bool)$a;
  }

  /**
   * Установить некоторые опции экспорта структуры
   */
  function setOptionsStruct($addIfNot, $addAuto, $addKav) {
    $this->addIfNot  = $addIfNot;
    $this->addAuto   = $addAuto;
    $this->addKav    = $addKav;
  }

  /**
   * УСтавноить некоорые опции экспорта данных
   */
  function setOptionsData($insFull, $insExpand, $insZapazd, $insIgnor) {
    $this->insFull   = $insFull;
    $this->insExpand = $insExpand;
    $this->insZapazd = $insZapazd;
    $this->insIgnor  = $insIgnor;
  }

  /**
   * Получить полный текст дампа
   * @ $clear - очистить объект (экономия памяти)
   */
  function get() {
    return $this->data;
  }

  /**
   * Заворачивает дамп в нужный вид и отправляет
   *
   * @ $type - тип отправки, значения:
   *   'textarea' - создаёт форму
   *   'zip' - создаёт архив и отправляет
   * @ $file - имя файла дампа для типа 'zip'
   */
  function send($type = 'textarea', $file=null) {
    return $this -> _sendSQLDamp($type, $file);
  }


  // ниже - внутренние функции, реализация

  /**
   * Возврвщает дамп структуры таблицы (sql запрос создания таблицы)
   * @$this->table - имя таблицы
   * @$addDrop - добавить к запросу удаление таблицы + форматировать через ;
   * @$this->comments - добавить комментарий
   */
  function exportStructure($addDelim = true, $addDrop = false) {
    $delim     = ";\r\n";
    $wr   = "\r\n";
    $tab = '  ';
    $dump = null;
    if ($this->comments) {
      $dump .= $wr.'--'.$wr.'-- Структура таблицы '.$this->table.$wr.'--'.$wr;
    }
    $ife = null;
    if ($addDrop) {
      $if = null;
      if ($this->addIfNot) {
        $if = 'IF EXISTS ';
      }
      $dump .= 'DROP TABLE '.$if.$this->tableb.$delim;
    }
    if ($this->addIfNot) {
        $ife = 'IF NOT EXISTS ';
    }
    /*$result = mysql_query('SHOW CREATE TABLE '.$this->tableb);
    if (!$result) {
      return null;
    }
    $row = mysql_fetch_array($result);
		if ($this->addIfNot) {
			$row['Create Table'] = str_replace('CREATE TABLE ', 'CREATE TABLE IF NOT EXISTS ', $row['Create Table']);
		}
		$dump .= $row['Create Table'].$delim;
		return $this->data .= $dump;*/
    $dump .= 'CREATE TABLE '.$ife. $this->tableb . ' ('.$wr.$tab;

    // дамп полей
    $result = mysql_query('SHOW FIELDS FROM '.$this->tableb);
    if (!$result) {
      return null;
    }
    $fields = array();
    $this->fields = array();
    while ($row = mysql_fetch_object($result)) {
      $this->fields []= $row;
      if ($this->addKav) {
        $field_info  = '`' . $row->Field . '` ' . $row->Type;
      } else {
        $field_info  = $row->Field . ' ' . $row->Type;
      }
      if ($row->Null != 'YES') {
			  $field_info .=  ' NOT NULL';
			}

	  if ($row->Type == 'timestamp') {
	  	if ($row->Default != '') {
			$row->Default = $row->Default == 'CURRENT_TIMESTAMP' ? $row->Default : '\''.$row->Default.'\'';
			$field_info  .=  ' default '.$row->Default;
		}
      } else if ($row->Default != null || ($row->Null != 'YES' && !strchr($row->Type, 'text'))) {
        if (!stristr($row->Extra, 'auto')) {
		  if ($row->Null != 'YES' && $row->Default=='') {

		  } else {
		  	$field_info .=  ' default \''.$row->Default.'\'';
		  }
        }
      } else if (!strchr($row->Type, 'text')) {
        $field_info .=  ' default NULL';
      }
      if ($row->Extra != '')
        $field_info .= ' '.$row->Extra;
      $fields []= $field_info;
    }
    // ключи
    $keys = array();
    $keys['PRI'] = $keys['UNI'] = $keys['MUL'] = $keys['FULL'] = array();
    $parts = array();
    $result = mysql_query('SHOW KEYS FROM '.$this->tableb);
    $x = $this->addKav ? '`' : '';
    while ($row = mysql_fetch_object($result)) {
      $row->Column_name = $x.$row->Column_name.$x;
      if ($row->Sub_part > 0) {
        $row->Column_name .= '('.$row->Sub_part.')';
      }
      if ($row->Key_name == 'PRIMARY') {
        $keys['PRI'][] = $row->Column_name;
      } else if ($row->Index_type == 'FULLTEXT') {
        $keys['FULL'][$row->Key_name][] = $row->Column_name;
      } else if ($row->Non_unique == '0') {
        $keys['UNI'][$row->Key_name][] = $row->Column_name;
      } else {
        $keys['MUL'][$row->Key_name][] = $row->Column_name;
      }
    }
    // обработка ключей
    $a = array();
    if (count($keys['PRI']) > 0) {
      $a [] = "PRIMARY KEY  (" . implode(",", $keys['PRI']) . ")";
    }
    if (count($keys['UNI']) > 0) {
			foreach ($keys['UNI'] as $k => $c) {
				$a [] = "UNIQUE KEY $x".$k."$x (" .implode(",",$c). ")";
			}
    }
    if (count($keys['MUL']) > 0) {
			foreach ($keys['MUL'] as $k => $c) {
				$a [] = "KEY $x".$k."$x (" .implode(",",$c). ")";
			}
    }
    if (count($keys['FULL']) > 0) {
			foreach ($keys['FULL'] as $k => $c) {
				$a [] = "FULLTEXT KEY $x".$k."$x (" .implode(",",$c). ")";
			}
    }
    // Загрузка
    $dump .=  implode(','.$wr.$tab, $fields);
    if (count($a) > 0) {
      $dump .= ','.$wr.$tab;
    }
    $dump .= implode(','.$wr.$tab, $a) . $wr;
    // кодировка, тип, автоинкремент
    $ai = null;
    $comment = null;
    $charset = 'utf8';
    $engine = 'MyISAM';
		$pack = null;
    if (!isset($this->tableStructure[$this->db])) {
      $result = mysql_query("SHOW TABLE STATUS FROM $this->db");
      $this->tableStructure[$this->db] = array();
      if ($result) {
        while ($row = mysql_fetch_object($result)) {
          $this->tableStructure [$this->db][]= $row;
        }
      }
    }
    foreach ($this->tableStructure[$this->db] as $row) {
      if ($row->Name == $this->table) {
        $ai = $row->Auto_increment;
        $charset = $row->Collation;
        $comment = $row->Comment;
        $engine = $row->Engine;
				$pack = $row->Create_options;
        break;
      }
    }
    if (!$this->addAuto || $ai == null) {
      $ai = null;
    } else {
      $ai = ' AUTO_INCREMENT='.$ai.' ';
    }
    if (strlen($pack) > 0) {
      $pack = ' '.$pack;
    }
    if ($comment != null) {
      $comment = ' COMMENT="'.$comment.'"';
    }
    if (strchr($charset, '_')) {
      $charset = str_replace(strchr($charset, '_'), '', $charset);
    }
    if ($addDelim) {
      $dump .= ") ENGINE=$engine DEFAULT CHARSET=$charset$pack$ai$comment".$delim;
    } else {
      $dump .= ") ENGINE=$engine DEFAULT CHARSET=$charset$pack$ai$comment".$wr;
    }
    return $this->data .= $dump;
  }


  /**
   * Возврвщает дамп данных таблицы (sql запрос )
   * @param string   тип экспорта (INSERT-REPLACE-UPDATE)
   * @param string   SQL условие
   * @param boolean  пропускать ли поля с auto_increment
   */
  function exportData($type = 'INSERT', $where = null, $skipAi=false){
    global $memory_limit;
    $delim = ";\r\n";
    $wr   = "\r\n";
    $tab = '    ';
    if (is_null($where) || strlen(trim($where)) < 2) {
      $sql = "SELECT * FROM $this->tableb";
    } else {
      if (stristr($where, 'WHERE ')) {
        $sql = "SELECT * FROM $this->tableb $where";
      } else {
        $sql = "SELECT * FROM $this->tableb WHERE $where";
      }
    }
    if (!$q_result = mysql_query($sql)) {
      return null;
    }
    $dump = null;
    if (mysql_num_rows($q_result) == 0) {
      return null;
    }
    // поля
    $exportedFields = array();
    if ($_POST['fields']) {
        $exportedFields = $_POST['fields'];
    }
    $f = $this->getFields($this->table);
    foreach ($f as $k => $v) {
        if ($exportedFields && !in_array($v->Field, $exportedFields) && ($type != 'UPDATE' || $v->Key != 'PRI')) {
        	unset($f[$k]);
        }
    }
    $fnames = array();
    foreach ($f as $i => $v) {
      if ($skipAi && $v->Extra != '') {
        continue;
      }
      $fnames[] = $v->Field;
    }
    // подготовка для INSERT
    $typeName = substr($type, 0, 6);
    if ($this->insZapazd && $typeName != 'UPDATE') {
      $type = $type.' DELAYED';
    }
    if ($this->insIgnor && $typeName != 'REPLAC') {
      $type = $type.' IGNORE';
    }
    if ($this->insFull) {
      $start = $type.' INTO '.$this->tableb.' (`'.implode('`,`', $fnames).'`) VALUES (';
    } else {
      $start = $type.' INTO '.$this->tableb.' VALUES (';
    }
    if ($this->insExpand && $typeName != 'UPDATE') {
      $dump .= $type.' INTO '.$this->tableb.' (`'.implode('`,`', $fnames).'`) VALUES ';
    }
    $count = 0;
    $isFullDump = true;
    while ($row = mysql_fetch_row($q_result)) {
      if (memory_get_usage() > $memory_limit) {
        $isFullDump = $count;
        break;
      }
      // UPDATE
      if ($typeName == 'UPDATE') {
        $a = array();
        $primary = array();
        foreach ($f as $i => $v) {
          if (isset($row[$i])) {
            if (stristr($v->Type, 'int')) {
              $val =  $row[$i];
            } else {
              $val =  '\'' . mysql_escape_string(trim($row[$i])) . '\'';
            }
          } else {
            $val = 'NULL';
          }
					$b = $v->Field;
          if ($this->addKav) {
            $b = '`'.$b.'`';
          }
          
          if ($v->Key == 'PRI') {
            $primary []= $b.'='.$val;
          } else {
            $a[] = $b . '=' . $val;
          }
        }
        $dump .= 'UPDATE '.$this->tableb.' SET '.implode(', ', $a).' WHERE '.implode(' AND ',$primary) . $delim;
      }

      // INSERT - REPLACE
      else if ($typeName == 'INSERT' || $typeName == 'REPLAC') {
        $values = array();
        foreach ($f as $i => $v) {
          if ($skipAi && $v->Extra != '') {
            continue;
          }
          if (isset($row[$i])) {
            if (stristr($v->Type, 'int')) {
              $val =  $row[$i];
            } else {
              $val =  '\'' . mysql_escape_string($row[$i]) . '\'';
            }
          } else {
            $val = 'NULL';
          }
          $values []= $val;
        }
        if ($this->insExpand) {
          if ($count == 50) {
            $count = 0;
            $dump  = substr($dump, 0, strlen($dump) - 3) . $delim;
            $dump .= $start . implode(',', $values) . ')' . $delim;
          } else {
            $dump .= '('.implode(',', $values) . ')'.",\r\n";
          }
        } else {
          $dump .= $start . implode(',', $values) . ')' . $delim;
        }
      }
      $count ++;
    }
    if ($this->insExpand) {
      $dump = substr($dump, 0, strlen($dump) - 3) . $delim;
    }
    if ($dump != null) {
      $dump = $dump . $wr;
      if ($this->comments) {
        $dump = $wr.'--'.$wr.'-- Дамп данных таблицы '.$this->table.$wr.'--'.$wr.$wr.$dump;
      }
    }
    $this->data .= $dump;
    return $isFullDump;
  }

  function _sendSQLDamp($type = 'textarea', $file=null) {
    if (is_null($file)) {
      $this->table != null ? $file = $this->table :  $file = $this->db;
    }
    // текстовое поле
    if ($type == 'textarea') {
      return
      '<textarea name="sql" rows="40" style="width:100%; font-size:11px; overflow:scroll" wrap="OFF">'.
      htmlspecialchars($this->get()).
      '</textarea>';
    // zip архив
    } else if ($type == 'zip') {
      if (headers_sent()) {
        return '<h3>headers_sent...</h3>';
      }
      ob_clean();
      header("Content-type: application/zip");
      header("Content-Disposition: attachment; filename=$file.zip");
      include_once 'zip.lib.php';
      $a = new zipfile();
      $a -> addFile($this->get(), $file.'.sql');
      echo $a ->file();
      exit();
    }
  }


    function exportByPart($table) {
        $limit = 50000;
        $this->data = '';
        $i = 0;
        while (true) {
            $start = $i * $limit;
          	elog("------- Export $table FROM $start TO $limit by part", 1);
        	$resultsCount = $this->exportData('INSERT', $where="1 LIMIT $start, $limit");
            if (empty($this->data)) {
            	break;
            }
            if (is_numeric($resultsCount)) {
              elog(' .... Exported only '.$resultsCount.' rec - memory limit '.memory_get_usage(), 0);
            }
            saveData2file($table.'.'.$start, $this->data, 'zip');
          	$this->data = '';
          	$i++;
        }
    }

  function getFields($table, $onlyNames=false) {
    $a = array();
    $result = mysql_query('SHOW FIELDS FROM ' . $table);
    if (!$result) {
      return false;
    }
    while ($row = mysql_fetch_object($result)) {
      if ($onlyNames) {
        $a []= $row->Field;
      } else {
        $a []= $row;
      }
    }
    return $a;
  }
}
?>
