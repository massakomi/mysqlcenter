<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2013
 */

/**
 * Zip file creation class.
 * Makes zip files.
 *
 * Based on :
 *
 *  http://www.zend.com/codex.php?id=535&single=1
 *  By Eric Mueller <eric@themepark.com>
 *
 *  http://www.zend.com/codex.php?id=470&single=1
 *  by Denis125 <webmaster@atlant.ru>
 *
 *  a patch from Peter Listiak <mlady@users.sourceforge.net> for last modified
 *  date and time of the compressed file
 *
 * Official ZIP file format: http://www.pkware.com/appnote.txt
 *
 * @access  public
 */
class zipfile
{
    /**
     * Array to store compressed data
     *
     * @var  array    $datasec
     */
    var $datasec      = array();

    /**
     * Central directory
     *
     * @var  array    $ctrl_dir
     */
    var $ctrl_dir     = array();

    /**
     * End of central directory record
     *
     * @var  string   $eof_ctrl_dir
     */
    var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";

    /**
     * Last offset position
     *
     * @var  integer  $old_offset
     */
    var $old_offset   = 0;


    /**
     * Converts an Unix timestamp to a four byte DOS date and time format (date
     * in high two bytes, time in low two bytes allowing magnitude comparison).
     *
     * @param  integer  the current Unix timestamp
     *
     * @return integer  the current date in a four byte DOS format
     *
     * @access private
     */
    function unix2DosTime($unixtime = 0) {
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

        if ($timearray['year'] < 1980) {
            $timearray['year']    = 1980;
            $timearray['mon']     = 1;
            $timearray['mday']    = 1;
            $timearray['hours']   = 0;
            $timearray['minutes'] = 0;
            $timearray['seconds'] = 0;
        } // end if

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
                ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    } // end of the 'unix2DosTime()' method


    /**
     * Adds "file" to archive
     *
     * @param  string   file contents
     * @param  string   name of the file in the archive (may contains the path)
     * @param  integer  the current timestamp
     *
     * @access public
     */
    function addFile($data, $name, $time = 0)
    {
        $name     = str_replace('\\', '/', $name);

        $dtime    = dechex($this->unix2DosTime($time));
        $hexdtime = '\x' . $dtime[6] . $dtime[7]
                  . '\x' . $dtime[4] . $dtime[5]
                  . '\x' . $dtime[2] . $dtime[3]
                  . '\x' . $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');

        $fr   = "\x50\x4b\x03\x04";
        $fr   .= "\x14\x00";            // ver needed to extract
        $fr   .= "\x00\x00";            // gen purpose bit flag
        $fr   .= "\x08\x00";            // compression method
        $fr   .= $hexdtime;             // last mod time and date

        // "local file header" segment
        $unc_len = strlen($data);
        $crc     = crc32($data);
        $zdata   = gzcompress($data);
        $zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
        $c_len   = strlen($zdata);
        $fr      .= pack('V', $crc);             // crc32
        $fr      .= pack('V', $c_len);           // compressed filesize
        $fr      .= pack('V', $unc_len);         // uncompressed filesize
        $fr      .= pack('v', strlen($name));    // length of filename
        $fr      .= pack('v', 0);                // extra field length
        $fr      .= $name;

        // "file data" segment
        $fr .= $zdata;

        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        // nijel(2004-10-19): this seems not to be needed at all and causes
        // problems in some cases (bug #1037737)
        //$fr .= pack('V', $crc);                 // crc32
        //$fr .= pack('V', $c_len);               // compressed filesize
        //$fr .= pack('V', $unc_len);             // uncompressed filesize

        // add this entry to array
        $this -> datasec[] = $fr;

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= "\x14\x00";                // version needed to extract
        $cdrec .= "\x00\x00";                // gen purpose bit flag
        $cdrec .= "\x08\x00";                // compression method
        $cdrec .= $hexdtime;                 // last mod time & date
        $cdrec .= pack('V', $crc);           // crc32
        $cdrec .= pack('V', $c_len);         // compressed filesize
        $cdrec .= pack('V', $unc_len);       // uncompressed filesize
        $cdrec .= pack('v', strlen($name) ); // length of filename
        $cdrec .= pack('v', 0 );             // extra field length
        $cdrec .= pack('v', 0 );             // file comment length
        $cdrec .= pack('v', 0 );             // disk number start
        $cdrec .= pack('v', 0 );             // internal file attributes
        $cdrec .= pack('V', 32 );            // external file attributes - 'archive' bit set

        $cdrec .= pack('V', $this -> old_offset ); // relative offset of local header
        $this -> old_offset += strlen($fr);

        $cdrec .= $name;

        // optional extra field, file comment goes here
        // save to central directory
        $this -> ctrl_dir[] = $cdrec;
    } // end of the 'addFile()' method


    /**
     * Dumps out file
     *
     * @return  string  the zipped file
     *
     * @access public
     */
    function file()
    {
        $data    = implode('', $this -> datasec);
        $ctrldir = implode('', $this -> ctrl_dir);

        return
            $data .
            $ctrldir .
            $this -> eof_ctrl_dir .
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries "on this disk"
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries overall
            pack('V', strlen($ctrldir)) .           // size of central dir
            pack('V', strlen($data)) .              // offset to start of central dir
            "\x00\x00";                             // .zip file comment length
    } // end of the 'file()' method

} // end of the 'zipfile' class


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
    while (($row = mysql_fetch_object($result)) !== false) {
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
    while (($row = mysql_fetch_object($result)) !== false) {
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
        while (($row = mysql_fetch_object($result)) !== false) {
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
    $f = $this->getFields($this->table);
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
              $val =  '\'' . mysql_escape_string($row[$i]) . '\'';
            }
          } else {
            $val = 'NULL';
          }
					$b = $v->Field;
          if ($this->addKav) {
            $b = '`'.$b.'`';
          }
          $a[] = $b . '=' . $val;
          if ($v->Key == 'PRI') {
            $primary []= $b.'='.$val;
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
        $file = $this->db != null ? $this->db : $this->table;
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
      $a = new zipfile();
      $a -> addFile($this->get(), $file.'.sql');
      echo $a ->file();

      exit;
    }
  }


    function exportByPart($table) {
        $limit = 50000;
        $this->data = '';
        $i = 0;
        while (true) {
            $start = $i * $limit;
          	elog("------- Export $table FROM $start TO $limit by part", 1);
        	$resultsCount = $this->exportData('REPLACE', $where="1 LIMIT $start, $limit");
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
function GET($name, $default=null) {
	if (isset($_GET[$name])) {
		return urldecode($_GET[$name]);
	} else {
		return $default;
	}
}
function POST($name, $default=null) {
	if (isset($_POST[$name])) {
		return $_POST[$name];
	} else {
	  return $default;
	}
}
function SESSION($name, $default=null) {
	if (isset($_SESSION[$name])) {
		return $_SESSION[$name];
	} else {
	  return $default;
	}
}
// Распечатка запроса в таблицу
function printSqlTable($sql, $data=null) {
    if ($data == null) {
        $result = mysql_query($sql);
        if (!$result) {
          echo mysql_error();
          return false;
        }
        $data = array();
        while ($row = mysql_fetch_assoc($result)) {
            $data []= $row;
        }
    }
    if (count($data) == 0) {
        return 'No data in  '.$sql;
    	return;
    }
    $content =  '
<style>
TABLE.optionstable {empty-cells:show; border-collapse:collapse;}
TABLE.optionstable TH {background-color: #eee}
TABLE.optionstable TH, TABLE.optionstable TD {border:1px solid #ccc; padding: 2px 4px; vertical-align: top;}
</style>
    <table class="optionstable">';
    foreach ($data as $k => $v) {
        if (!isset($headersPrinted)) {
        	$headersPrinted = 1;
        	$content .= '<tr>';
            foreach ($v as $k1 => $v1) {
            	$content .= '<th>'.$k1.'</th>';
            }
            $content .= '</tr>';
        }
       // extract($v);
    	$content .= '<tr>';
        foreach ($v as $k2 => $v2) {
        	$content .= '<td>'.htmlspecialchars($v2).'</td>';
        }
        $content .= '</tr>';
    	//echo '<br /><a href="'.$v['url'].'">'.$v['title'].'</a> - '.$v['fix_online'];
    }
    $content .= '</table>';
    unset($headersPrinted);
    return $content;
}
function getServerVersion() {
	$result = mysql_query('SELECT VERSION() AS version');
	if ($result !== false) {
		$row   = mysql_fetch_array($result);
		$match = explode('.', $row[0]);
	}
	if (!isset($row)) {
		$vi = 32332;
		$vs = '3.23.32';
	} else{
		$vi = (int)sprintf('%d%02d%02d', $match[0], $match[1], intval($match[2]));
		$vs = $row[0];
	}
	return $vs;
}
function elog($text, $newline=0) {
  global $logfile;
  echo str_repeat('<br>', $newline).$text;
  if (POST('log') == 1 && $logfile != false) {
    fwrite($logfile, str_repeat("\r\n", $newline).strip_tags($text));
  }
}

function stripslashesRecursive($array) {
  if (is_array($array)) {
    return array_map('stripslashesRecursive', $array);
  }
  return stripslashes($array);
}
/**
 * Время форматирует в русское "Вчера-сегодня-позавчера и последние дни недели"
 *
 * @package date
 * @param string  Формат date() для обычного форматирование
 * @param integer Timestamp дата
 * @return string Отформатированная дата
 */
function date2rusString($format, $ldate) {
    // дата сегодня 00:00
    $tmsTodayBegin = strtotime(date('m').'/'.date('d').'/'.date('y'));
    // дата заданного времени 00:00
    $tmsBegin = strtotime(date('m',$ldate).'/'.date('d',$ldate).'/'.date('y',$ldate));
    $params   = array('Сегодня', 'Вчера', 'Позавчера');
    $weekdays = array('Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота');
    for ($i = 0; $i <= 6; $i ++ ) {
        $tms = $tmsTodayBegin - 3600 * 24 * $i;
        if ($tmsBegin == $tms) {
            if (isset($params[$i])) {
                return $params[$i].', '.date('H-i', $ldate);
            } else {
                return $weekdays[date('w', $ldate)].', '.date('H-i', $ldate);
            }
        }
    }
    return date($format, $ldate);
}
function execSql($content, $type='', $replace_from, $replace_to, $max_query=null, $exitOnError=false) {
    $content = str_replace("\r", '', $content);
    if ($type == 'slow') {
        $errors = array();
        $affected = $start = $c = 0;
        while (($q = substr($content, $start, strpos($content, ";\n", $start) - $start)) !== false) {
            $c ++;
            $start = strpos($content, ";\n", $start) + 3;
            if (empty($q)) {
                continue;
            }
            if ($replace_from != '') {
                $q = str_replace($replace_from, $replace_to, $q);
            }
            if (!mysql_unbuffered_query($q)) {
                $e = mysql_error();
                $errors []= $e;
                if ($exitOnError) {
                	exit("Запрос: $q Ошибка: $e");
                }
            } else {
                $affected += mysql_affected_rows();
            }
        }
    } else {
        $array = explode(";\n", $content);
        if ($max_query > 0) {
            $array = array_slice($array, 0, $max_query);
        }
        $errors = array();
        $c = count($array);
        $affected = 0;
        for ($i = 0; $i < $c; $i ++) {
            $q = trim($array[$i]);
            if (empty($q) || (strpos($q, '--') === 0 && strpos($q, "\n") === false)) {
                continue;
            }
            if ($replace_from != '') {
                $q = str_replace($replace_from, $replace_to, $q);
            }
            if (!mysql_unbuffered_query($q)) {
                $e = mysql_error();
                $errors []= $e;
                if ($exitOnError) {
                	exit("Запрос: $q Ошибка: $e");
                }
            } else {
                $affected += mysql_affected_rows();
            }
        }
    }
    return array($errors, $c, $affected);
}
function addRow($data, $t='td', $st='') {
    $str = "\n".'<tr'.$st.'>';
    foreach ($data as $k => $v) {
    	$str .= "\n".'    <'.$t.' valign="top">'.$v.'</'.$t.'>';
    }
    $str .= "\n".'<tr>';
    return $str;
}
function getFields($table, $onlyNames=false) {
    if (empty($table)) {
        return array();
    }
	$a = array();
	$table = str_replace('`', '``', $table );
	$result = mysql_query('SHOW FIELDS FROM `'.$table.'`');
	if (!$result) {
		return false;
	}
	while ($row = mysql_fetch_object($result)) {
		if ($onlyNames) {
			$a []= $row->Field;
		} else {
			$a [$row->Field]= $row;
		}
	}
	return $a;
}
function getRequestParam($param, $default='') {
    if (array_key_exists($param, $_POST)) {
       	setcookie($param, $_POST[$param], time() + 86400*180, '/');
       	$_SESSION[$param] = $_POST[$param];
    	return $_POST[$param];
    }
    if (array_key_exists($param, $_GET)) {
       	setcookie($param, $_GET[$param], time() + 86400*180, '/');
       	$_SESSION[$param] = $_GET[$param];
    	return $_GET[$param];
    }
    if (array_key_exists($param, $_SESSION)) {
    	return $_SESSION[$param];
    }
    if (isset($_COOKIE[$param])) {
    	return $_COOKIE[$param];
    }
    return $default;
}
// РЕДАКТИРОВАНИЕ УРЛА
// url('id=5') - добавит к текущему QUERY_STRING. в случае если уже есть id - заменит
// url('id=5', 'id=10&mode=5') -
function url($add='', $query='') {
  $httpHost = 'http://'.$_SERVER['HTTP_HOST'];
  $path     = $_SERVER['SCRIPT_NAME'];
  $query    = $query == '' ? $_SERVER['QUERY_STRING'] : $query;
  if ($query == '') {
  	return $path.'?'.$add;
  }
  parse_str($query, $currentAssoc);
  parse_str($add, $addAssoc);
  if (is_array($addAssoc)) {
    foreach ($addAssoc as $k => $v) {
      $currentAssoc [$k]= $v;
    }
  }
  $a = array();
  foreach ($currentAssoc as $k => $v) {
    $a []= $v == '' ? $k : "$k=$v";
  }
  return $path.'?'.implode('&', $a);
}
function msg($msg, $error='') {
	echo '<div style="border:1px solid blue; padding:5px; width:50%;">'.$msg;
	if ($error) {
		echo "<br /><span style='color:red; font-size:10px;'>Error: $error</span>";
	}
	echo '</div>';
}
function query($sql) {
	$result = mysql_query($sql);
	$e = mysql_error();
	$error = $e != null && substr($e, 0, 15) != 'Duplicate entry' ? $e : '';
    msg('Запрос выполнен: '.$sql, $error);
	$type = substr(strtolower(trim($sql)), 0, 6);
	if ($type == 'insert') {
        return mysql_insert_id();
	} else if ($type == 'update' || $type == 'delete') {
        return mysql_affected_rows();
    } else {
        return $result;
    }
}
function mysqlUpdate($table, $data, $where='') {
    $values = array();
    foreach ($data as $k => $v) {
         if (!is_numeric($v)) {
        	$v = '"'.mysql_escape_string($v).'"';
        }
    	$values []= $k.'='.$v;
    }
    $sql = ' UPDATE `'.$table.'` SET '.implode(', ', $values).' WHERE '.$where;
    return mysql_query($sql);
}
function mysqlInsert($table, $data, $m='INSERT') {
    $values = array();
    foreach ($data as $k => $v) {
        if (!is_numeric($v)) {
        	$v = '"'.mysql_escape_string($v).'"';
        }
    	$values []= $v;
    }
    $sql = $m.' INTO `'.$table.'` (`'.implode('`, `', array_keys($data)).'`) VALUES ('.implode(',', $values).')';
    return mysql_query($sql);
}
function redirect($url, $seconds=0) {
  if (!headers_sent()) {
    header('Location: '.$url);
    exit;
  } else {
    echo '
    <script language="javascript">
    setTimeout(function () {
        window.location = "'.$url.'";
    }, '.($seconds * 1000).');
    </script>';
  }
}
function generatePagesLinks($limit, $start, $countAll) {
    $pageLinks = '<div class="pages">';
    $pageCount = ceil($countAll / $limit);
    if ($pageCount == 1) {
    	return '';
    }
    $j = 0;
    $floatLimit = 50;
    if ($start > $floatLimit) {
    	$pageLinks .= '<a href="'.url('start=0').'">1...</a> ';
    }
    for ($i = max(1, $start - $floatLimit); $i <= $pageCount; $i ++) {
        if ($j > $floatLimit * 2) {
        	break;
        }
        $st = '';
        if ($i - 1 == $start) {
        	$st = ' style="font-weight:bold; color:#FF0000; background-color:green; color:white "';
        }
    	$pageLinks .= '<a'.$st.' href="'.url('start='.($i-1)).'">'.$i.'</a> ';
    	$j ++;
    }
    if ($pageCount > $floatLimit * 2) {
    	$pageLinks .= '<a href="'.url('start='.($pageCount-1)).'">...</a> ';
    }
    $pageLinks .= '</div>';
    return $pageLinks;
}
// Возвращает наиболее подходящий код инпут-формы для редактирования параметра
function getField($name, $title, $type, $req, $values) {
    $defaultValue = isset($_POST[$name]) ? $_POST[$name] : (isset($values[$name]) ? $values[$name] : '');
    $defaultValue = htmlspecialchars($defaultValue);
    $add = 'id="f-'.$name.'"';
    if ($req) {
    	//$this-> = $;$add .= ' required';
    }
    if ($type == 'select') {
        $a = explode('|', $values);
        $html = '';
        foreach ($a as $key => $val) {
            if ($key == count($a)-1) {
                $add .= ' checked="checked"';
            }
            $html .= '<label><input type="radio" name="'.$name.'" value="'.$val.'"'.
                $add.'> '.$val.'</label>';
        }
    } else if ($type == 'boolean') {
        if (intval($defaultValue) != 0 /*&& (!$isAdd || $param['default_value'] != 1)*/) {
            $add .= ' checked="checked"';
        }
        $html = '<div class="text"><input type="checkbox" name="'.$name.'" value="1"'.$add.' /></div>';
    } else if ($type == 'integer') {
        $html = '<input type="text" name="'.$name.'" value="'.$defaultValue.'" style="width:100px" class="text"'.$add.' />';
    } else if ($type == 'date') {
        $html = '<input type="text" name="'.$name.'" value="'.$defaultValue.'" style="width:100px" class="text datepicker"'.$add.' />';
    } else if ($type == 'file') {
        $html = '<input type="file" name="'.$name.'" class="text" />';
    } else if ($type == 'text') {
        $html = '<textarea name="'.$name.'"'.$add.' rows=4 cols=80>'.$defaultValue.'</textarea>';
    } else {
        $html = '<input type="text" name="'.$name.'" value="'.$defaultValue.'" class="text"'.$add.' />';
    }
    return $html;
}
// todo перенести наверх
function includePclZip() {
    $success = false;
    $dirs = array('.', '..');
    addDirs($dirs, '.', 1);
    addDirs($dirs, '..');
    addDirs($dirs, '../..');
    foreach ($dirs as $dir) {
    	$success = includeFromFolder($dir);
        if ($success) {
            break;
        }
    }

    if (!$success) {
        echo '<p style="color:red">Файл pclzip.lib.php не найден '.getcwd().'.</p>';
    }
    return $success;
}
function addDirs(&$dirs, $dir, $recurs=0) {
    $a = scandir($dir);
    foreach ($a as $k => $v) {
        if ($v != '.' && $v != '..' && is_dir($dir.'/'.$v)) {
        	$dirs []= $dir.'/'.$v;
            if ($recurs) {
            	addDirs($dirs, $dir.'/'.$v, $recurs);
            }
        }
    }
}
function includeFromFolder($dir) {
    $zlib = $dir . '/pclzip.lib.php';
    if (file_exists($zlib)) {
        include_once $zlib;
        return true;
    }
    return false;
}































//------------------------------------------------------------------------------------------------------------
//                                      Стартуем!
if (POST('log') == 1) {
  global $logfile;
  $logfile = fopen('log.txt', 'w+');
}

global $memory_limit;
ini_set('short_open_tag', true);
$memory_limit = (intval(ini_get('memory_limit'))*1024*1024)/2;
date_default_timezone_set('Europe/London');


if (ini_get('magic_quotes_gpc') == '1') {
    $_POST = stripslashesRecursive($_POST);
}

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'on');
//set_time_limit(300);
//ini_set('memory_limit', '256M')
if (GET('action') == 'logout') {
  $_SESSION['db_name'] = $_SESSION['db_user'] = $_SESSION['db_server'] = $_SESSION['db_pass'] = '';
  $_COOKIE['db_name'] = $_COOKIE['db_user'] = $_COOKIE['db_server'] = $_COOKIE['db_pass'] = '';
}


// Последний выполненные запросы из сессии
$lastSqls = isset($_SESSION ['sql']) ? $_SESSION ['sql'] : array();


$user   = getRequestParam('db_user');
$pass   = POST('db_pass', SESSION('db_pass'));
$server = getRequestParam('db_server');
$database = getRequestParam('db_name');

define('MS_APP_NAME', 'MySQL exporter mini');


// Соединение с базой данных
$paramsall = array(
  array($server, $user, $pass)
);
foreach ($paramsall as $params) {
  $conn = @call_user_func_array('mysql_connect', $params);
  if ($conn) {
    list($server, $user, $pass) = $params;
    break;
  }
}




// Выполняем запросы
if (POST('ajax') == 1) {
    echo 123;
	exit;
}
















if (POST('ex_type') != 'zip') {
  header("Content-Type: text/html; charset=utf-8");
} else {
  ob_start();
}
?><html>
  <head>
  	<meta charset="utf-8" />
  	<title><?php echo MS_APP_NAME ?></title>
    <script language="javascript">
/*
    xla = new XLAjax('', function (data) {
        eval(data)
    });;
    xla.send('param=1');
*/
// action должен быть строкой УРЛ "name=value&..." (form-urlencoded?)
function XLAjax(file, callback) {

	this.createObject = function() {
		var request = null;
		if (typeof XMLHttpRequest != "undefined") {
			try {
				request = new XMLHttpRequest();
			} catch(e) {
				request = null;
			}
		} else if(window.ActiveXObject) {
			try {
				request = new ActiveXObject("Msxml2.XMLHTTP");
			} catch(e) {
				try {
					request = new ActiveXObject("Microsoft.XMLHTTP");
				} catch(e) {
					request = null;
				}
			}
		}
		return request;
	};

	this.handleResponse = function(e) {
		if (xla.http.readyState == 4){
			var response = xla.http.responseText;
			// response = response.replace(/"/g, '\\"');
			response = response.replace(/\s+/g, ' ');
			xla.callback(response);
		}
		xla.log(e);
	};

	// type = объект типа Event (есть свойство type)
	this.log = function(event) {
       //var type = event.type;
	   //document.getElementById('ready').innerHTML += type+'<br />';

	   return;
        // Самая первый статус отличается в FF=1, в Хроме=2
        if (type == 'readystatechange' && xla.http.readyState == 1) {
        	document.getElementById('ready').innerHTML += '<br />';
        }

        var text = 'any';
        // В случае php ошибки статус=200 и приходит текст ошибки с тегами.
        text = text.replace(/>/g, '&gt;').replace(/</g, '&lt;');

        document.getElementById('ready').innerHTML += '<div>['
        +type+'] readyState='+xla.http.readyState+' "'+text
        +'" status='+xla.http.status+' text='+xla.http.statusText
        +'</div>';
    };

	// action должен быть строкой УРЛ "name=value&..." (form-urlencoded?)
	this.send = function(action) {
        xla.http.send(action == undefined ? '' : action);
	};

	this.callback = callback;

	this.http = this.createObject();
	this.http.open('POST', file, true);
	this.http.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8");
	this.http.onreadystatechange = this.handleResponse;
	this.http.onprogress = this.log;
	this.http.onload = this.log;
	this.http.onerror = this.log;
}
    xla = new XLAjax('', function (data) {
        eval(data)
    });
    </script>
    <style>
    a {text-decoration:none;}
    </style>
  </head>
  <body>
  <div style="width:20%; float:left; font-size:24px; color:#6D8FB3;"><b>MySQL exporter mini</b></div>

<?php
if ($conn == false || $user == '') {
?>
<form class="login" method="post" style="display:block; clear:both" action="exp.php">
    No server connection
    <div><input type="text" name="db_server" value="<?php echo $server?>" /> <b>db_server</b></div>
    <div><input type="text" name="db_user" value="<?php echo $user?>" /> <b>db_user</b></div>
    <div><input type="password" name="db_pass" /> <b>db_pass</b></div>
    <div><input type="text" name="db_name" value="<?php echo $database?>" /> <b>db_name</b></div>
    <div><input type="submit" value="Connect" /></div>
</form>
<style>
form.login div {padding:5px;}
form.login div input {font-size:20px;;}
</style>
<?php
    exit;
}
$_SESSION['db_user'] = $user;
$_SESSION['db_pass'] = $pass;
$_SESSION['db_server'] = $server;

?>

  <div style="width:80%; float:left; clear:all; font-size:14px; color:#666">
    <span style="color:#ccc">max_time=</span><?php echo ini_get('max_execution_time')?>
    <span style="color:#ccc">memory_limit=</span><?php echo ini_get('memory_limit')?>
    <span style="color:#ccc">php </span><?php echo phpversion()?>
    <span style="color:#ccc">mysql </span><?php echo getServerVersion()?>
  </div>
<?php

























echo "Connected to $server as $user. ";

// Выбор базы данных
$error = '';
if (empty($database)) {
    $error = 'Please select database';
} else if (!mysql_select_db($database)) {
    $error = "Cannot select database $database. Please try again.";
}
if ($error != '') {
    echo "<div style='color:red'>$error</div>";
    $db_list = mysql_list_dbs($conn);
    while ($row = mysql_fetch_object($db_list)) {
    echo '<span style="cursor:pointer" onclick="document.forms[\'loginForm\'][\'db_name\'].value=this.innerHTML; document.forms[\'loginForm\'].submit()">'.$row->Database . "</span><br />";
    }

    ?>
    <form method="post" name="loginForm">
    <div><input type="text" name="db_name" /> <b>db_name</b></div>
    <div><input type="submit" value="Select" /></div>
    </form>
    <?php
    exit;
}
$_SESSION['db_name'] = $database;
echo "Database: '$database'";
echo " <a href='?action=logout'>Выйти</a> &nbsp;&nbsp;
<a href='".$_SERVER['PHP_SELF']."'>Экспорт/импорт</a> &nbsp;
<a href='?action=tables'>Таблицы</a> &nbsp;
<a href='?action=zip'>Архив</a>";

// Очищающие запросы
$a = array(
	"SET NAMES utf8",
	"SET character_set_database = utf8",
	"SET character_set_server = utf8"
);
array_map('mysql_query', $a);































//------------------------------------------------------------------------------------------------------------
//                                      Таблицы базы данных, редактирование
if (GET('action') == 'tables') {


?>
<style>
BODY {font-family:Arial; }
H2 {margin:10px; font-size:20px;}
TABLE.optionstable {empty-cells:show; border-collapse:collapse; margin:10px;}
TABLE.optionstable TH {background-color: #eee}
TABLE.optionstable TH, TABLE.optionstable TD {border:1px solid #ccc; padding: 3px; vertical-align: top;font-size:13px;}
TABLE.right TD { text-align:right;}
TABLE.optionstable A {text-decoration:none;}
TABLE.optionstable A:hover {text-decoration:underline;}
</style>
<?php



if (isset($_GET['table'])) {

    if ($_GET['action'] == 'removeAll') {
        $result = mysql_query('SHOW TABLE STATUS');
        while ($v = mysql_fetch_object($result)) {
            query('DROP TABLE `'.$v->Name.'`');
        }
    }

    switch (@$_GET['mode']) {
    case 'delete':
        echo '<br />Удаляем таблицу '.$_GET['table'];
        query('DROP TABLE '.$_GET['table']);
        break;
        
    case 'truncate':
        echo '<br />Очищаем таблицу '.$_GET['table'];
        query('TRUNCATE TABLE '.$_GET['table']);
        break;
        
    case 'fields':
        $url = 'exp.php?action=tables&table='.$_GET['table'];
        echo '<h2>Структура '.$_GET['table'].' <a style="font-size:14px; text-decoration:none;" href="'.$url.'" title="Показать другие действия" onmouseover="this.nextSibling.style.display=\'inline\'">≡</a><span style="display:none; margin-left:100px"> <a href="'.$url.'&mode=delete" onclick="if (!confirm(\'Удалить '.$_GET['table'].'?\')) return false;">удал</a> <a href="'.$url.'&mode=truncate" onclick="if (!confirm(\'Очистить '.$_GET['table'].'?\')) return false;">очист</a></span></h2>';
        if (GET('smode') == 'delete' && GET('field')) {
        	query('ALTER TABLE '.$_GET['table'].' DROP '.GET('field'));
        }
        
        $fields = getFields($_GET['table'], $onlyNames=false);
        
        $rows = '';
        foreach ($fields as $field => $p) {
            $p->actionsLinks = '<a href="'.url('smode=delete&field='.$field).'">del</a>
                      <a href="'.url('smode=edit&field='.$field).'">edit</a>';
            $rows .= addRow($p);
        }



        ?>
        <table border="0" cellspacing="0" cellpadding="0" class="optionstable">
        <tr>
<?php
$data = array_pop($fields);
foreach ($data as $k => $v) {
echo '            <th align="center" valign="top">'.$k.'</th>';
}
?>
        </tr>
        <?php echo $rows?>
        </table>
        <?php
        exit;
        
        
        
        
        
    // Просмотр таблицы
    default:

        $url = 'exp.php?action=tables&table='.$_GET['table'];
        echo '<h2>Просмотр данных '.$_GET['table'].' <a style="font-size:14px; text-decoration:none;" href="'.$url.'&mode=fields" title="Показать другие действия" onmouseover="this.nextSibling.style.display=\'inline\'">≡</a><span style="display:none; margin-left:100px"> <a href="'.$url.'&mode=delete" onclick="if (!confirm(\'Удалить '.$_GET['table'].'?\')) return false;">удал</a> <a href="'.$url.'&mode=truncate" onclick="if (!confirm(\'Очистить '.$_GET['table'].'?\')) return false;">очист</a></span></h2>';
        
        $fields = getFields($_GET['table'], $onlyNames=false);
        
        if (GET('tmode') == 'delete' && GET('where')) {
        	query('DELETE FROM '.$_GET['table'].' WHERE '.GET('where').' LIMIT 1');
        }

function processEdit($fields) {

    if (GET('where')) {
        $data = mysql_query('SELECT * FROM '.$_GET['table'].' WHERE '.GET('where').' LIMIT 1');
        $data = mysql_fetch_assoc($data);
    }


    if (count($_POST)) {
        if (GET('where')) {
        	$res = mysqlUpdate($_GET['table'], $_POST, GET('where'));
        } else {
            $res = mysqlInsert($_GET['table'], $_POST);
        }
        if ($res) {
            msg('Обновлено!', $error='');
        } else {
            msg('Ошибка', mysql_error());
        }
    }



    $formFields = '';
    foreach ($fields as $v) {
        $typeStr = '';
        if (strchr($v->Type, 'int') !== false) {
        	$typeStr = 'integer';
        } else if (strchr($v->Type, 'varchar') !== false) {
        	$typeStr = '';
        } else if (strchr($v->Type, 'text') !== false) {
        	$typeStr = 'text';
        }

        if ($v->Null != 'YES') {
            $title = '<b style="color:red">'.$v->Field.':</b>';
        } else {
            $title = '<b>'.$v->Field.':</b>';
        }
        $html = getField($v->Field, $title, $typeStr, $v->Null != 'YES', GET('where') ? $data : '');

        ob_start();
        var_dump($v->Default);
        $Default = ob_get_contents();
        ob_end_clean();

        $formFields .= '
        <div>
            <div style="width:100px; float:left">'.$title.'</div>
            <div style="width:700px; float:left">'.$html.'</div>
            <div>'.$v->Type.' <b>'.($v->Null == 'YES' ? 'Null' : 'Required').'</b> <span style="color:green">'.($v->Key != '' ? $v->Key.' Key' : '').'</span> <span style="color:blue">'.$v->Extra.'</span> Default='.$Default.'</div>
            <div style="clear:both; float:none"></div>
        </div>';
    }
/*
[Field] => id
[Type] => int(11)
[Null] => NO
[Key] => PRI
[Default] =>
[Extra] => auto_increment
*/

?>
<style>
FORM.myform DIV {margin-bottom:5px; width:100%;}
FORM.myform input {margin:0; padding:0;}
FORM.myform INPUT.text {width:90%;}
</style>
<form method="post" class="myform">
<?php echo $formFields?>
<div><input type="submit" value="Сохранить" /></div>
</form>
<?php
}

        if (GET('tmode') == 'edit' && GET('where')) {
            processEdit($fields);
        }



        if (GET('tmode') == 'add') {
            processEdit($fields);
        }




        $order = GET('order', 1);
        if (strpos($order, '-') === 0) {
        	$order = substr($order, 1) . ' DESC';
        }

        $limit = 200;
        $start = GET('start');
        $filter = GET('filter');
        $cut = GET('cut', 500);
        $wrap = GET('wrap', 50);
        $hsc = isset($_GET['cut'])? GET('hsc') : 1;

        $where = '';
        if ($filter != '') {
            $where = array();
            foreach ($fields as $k => $v) {
            	$where []= $v->Field.' LIKE "%'.$filter.'%"';
            }
            $where = ' WHERE '.implode(' OR ', $where);
        }

        $result = mysql_query('
        SELECT COUNT(*) AS c FROM '.$_GET['table'].$where);
        $v = mysql_fetch_object($result);
        $countAll = $v->c;
        $pageLinks = generatePagesLinks($limit, $start, $countAll);

        $pk = '';
        foreach ($fields as $k => $v) {
            if ($v->Key == 'PRI') {
            	$pk = $v->Field;
            }
        }

        $result = mysql_query('
        SELECT * FROM '.$_GET['table']."
        $where
        ORDER BY $order LIMIT ".($start * $limit).", $limit");
        $rows = '';
        while ($v = mysql_fetch_object($result)) {
            $row = array();
            if ($pk) {
                $row []= '<a onclick="if (!confirm(\'Удалить '.$v->$pk.'?\')) return false;" href="'.url('tmode=delete&where='.$pk.'='.$v->$pk).'">x</a>&nbsp;<a href="'.url('tmode=edit&where='.$pk.'='.$v->$pk).'">edit</a>';
            } else {
                $row []= 'no primary key';
            }
            foreach ($fields as $field => $p) {
                if ($cut > 0 && mb_strlen($v->$field) > $cut) {
                	$v->$field = mb_substr($v->$field, 0, $cut).'...';
                }
                if ($hsc) {
                	$v->$field = htmlspecialchars($v->$field);
                }
                if ($wrap > 0) {
                	$v->$field = wordwrap($v->$field, $wrap, '<br />', 1);
                }
            	$row []= $v->$field;
            }
            $rows .= addRow($row);
        }
        
        echo $pageLinks;
        ?>
        <style>
        DIV.pages A {background-color:#FFFFCC; border:1px solid #ccc; padding:2px 5px; text-decoration:none;}
        DIV.pages {line-height:200%;}
        </style>
        
        <label onclick="location='<?php if (GET('fullheaders') == 1) {echo str_replace('&fullheaders=1', '', $_SERVER['REQUEST_URI']);} else {echo url('fullheaders=1');}?>'"><input type="checkbox" name="fullheaders" value="1" <?php if (GET('fullheaders') == 1) {echo 'checked';} ?> /> полные заголовки</label>



<form method="get" style="display:inline;">
<?php
foreach ($_GET as $k => $v) {
if (!in_array($k, array('wrap', 'cut', 'filter', 'hsc'))) {
	echo '<input type="hidden" name="'.$k.'" value="'.$v.'" />';
}
}
?>

<input type="text" name="wrap" value="<?php echo $wrap?>" size=2 /> wrap
<input type="text" name="cut" value="<?php echo $cut?>" size=2 /> cut
<input type="text" name="filter" value="<?php echo $filter?>" /> filter
<input type="checkbox" name="hsc" value="1" <?php echo ($hsc?' checked':'')?>/> htmlspecialshars
<input type="submit" value="Применить" />
</form>

<a href="exp.php?action=tables&table=<?=GET('table')?>&mode=add&tmode=add">Добавить строку</a>
        
        <table border="0" cellspacing="0" cellpadding="0" class="optionstable">
        <tr>
        <th>&nbsp;</th>
<?php
foreach ($fields as $k => $v) {
if (GET('fullheaders') == 1) {
	$header = $k;
} else {
    $header = str_replace('_', '<br />', $k);
}
echo '            <th align="center" valign="top"><a href="'.url('order='.($order == $k ? '-'.$k : $k)).'">'.$header.'</a></th>';
}
?>
        </tr>
        <?php echo $rows?>
        </table>
        <?php
        echo $pageLinks;
    	exit;
    }
}



echo '<h2>Список таблиц базы данных <i>'.$database.'</i></h2>';
$result = mysql_query('SHOW TABLE STATUS');

$rows = '';
while ($v = mysql_fetch_object($result)) {
    if ($v->Update_time) {
    	$updateTime = date2rusString('d.m.Y H:i', strtotime($v->Update_time));
    	if (strpos($updateTime, 'дня') !== false || strpos($updateTime, 'ера') !== false) {
    		$updateTime = "<b>$updateTime</b>";
    	}
    } else {
        $updateTime = '-';
    }
	$url = '?action=tables&table='.$v->Name.'';
    $rows .= addRow(array(
        '<div style="text-align:left;"><a href="'.$url.'">'.$v->Name.'</a></div>',
        $v->Rows,
        number_format(round(($v->Data_length + $v->Index_length)/1024, 1), 1, '.', ''),
        $updateTime,
        '<span style="color:#ccc">'.$v->Engine.'</span>',
        substr($v->Collation, 0, strpos($v->Collation, '_')),
        '<div style="text-align:left;"><a href="'.$url.'&mode=fields">Структура</a>
        <a href="#" title="Показать другие действия" onmouseover="this.nextSibling.style.display=\'inline\'">≡</a><span style="display:none;"> <a href="'.$url.'&mode=delete" onclick="if (!confirm(\'Удалить '.$v->Name.'?\')) return false;">удал</a>
        <a href="'.$url.'&mode=truncate" onclick="if (!confirm(\'Очистить '.$v->Name.'?\')) return false;">очист</a></span></div>'
    ));
}
?>
<table border="0" cellspacing="0" cellpadding="0" class="optionstable right">
<tr>
<th align="center" valign="top">Таблица</th>
<th align="center" valign="top">Рядов</th>
<th align="center" valign="top">Размер</th>
<th align="center" valign="top">Дата</th>
<th align="center" valign="top">Engine</th>
<th align="center" valign="top">Кодировка</th>
<th align="left" valign="top">Действия</th>
</tr>
<?php echo $rows; ?>
</table>

    <a href="/exp.php?action=tables&action=removeAll" onclick="if (!confirm('Подтвердите')) return false;">Удалить все таблицы</a>

<?php
exit;
}



















//------------------------------------------------------------------------------------------------------------
//                                      Архивирование
if (GET('action') == 'zip') {

    $res = includePclZip();
    if (!$res) {
    	exit;
    }
    
    function zipFilesAdd($files, $index) {
        $file = "files-$index.zip";
    	//echo '<hr />';
        echo '<br />'.$file;
        if (file_exists($file)) {
        	echo ' ... file exists SKIP!';
        } else {
            $zip = new PclZip($file);
            $zip->add($files);
        }
    }
    
    function zipFiles($cdir, &$files, $max, $count, $extension, $exclude, &$index, $level=0) {
        $dirs = scandir($cdir);
        $break = false;
        foreach ($dirs as $k => $v) {
            if ($v == '.' || $v == '..' || in_array($v, $exclude)) {
            	continue;
            }
            $dir = $cdir .'/'. $v;
            if ($max > 0 && $index >= $max) {
                if (count($files) > 0) {
                    zipFilesAdd($files, $index);
                	$files = array();
                }
                $break = true;
            	break;
            }
            if ($index % $count == 0 && count($files) > 0) {
                zipFilesAdd($files, $index);
            	$files = array();
            }
            if (is_dir($dir)) {
                $break = zipFiles($dir, $files, $max, $count, $extension, $exclude, $index, $level + 1);
            } else {
                foreach ($extension as $ext) {
                    if (strpos($dir, '.'.$ext)) {
                        $index ++;
                        //echo '<br />'.$index.') '.$dir;
                    	$files []= $dir;
                    }
                }
            }
        }
        return $break;
    }

    $count     = POST('count', 100);
    $max       = POST('max', 500);
    $extension = POST('extension', 'php,js');
    $exclude   = POST('exclude', '.svn');
    
?>

<h2 style="margin:0px;">Создать архив по частям</h2>
<form method="post" class="myform">

Максимальное кол-во файлов в одном архиве <input type="text" name="count" value="<?php echo $count?>" size="5" />
<br />Максимальное кол-во файлов всего <input type="text" name="max" value="<?php echo $max?>" size="5" />

<br />включить только файлы с расширением: <input type="text" name="extension" value="<?php echo $extension?>" />
<br />исключить файлы/папки по имени: <input type="text" name="exclude" value="<?php echo $exclude?>" />

<br /><input type="submit" value="Давай начинай свою шарманку" />
</form>
<?php
    if ($_POST) {
        $index = 0;
        $files = array();
        $extension = explode(',', $extension);
        $exclude   = explode(',', $exclude);
    	zipFiles('.', $files, $max, $count, $extension, $exclude, $index, $level=0);
    	echo '<h3>Вроде бы всё</h3>';
    }
    exit;
}

















//------------------------------------------------------------------------------------------------------------
//                                      Форма выбора действия
if (!isset($_POST['action']) || POST('action') == 'sql') {

    $existFolder = '';
    // $perm = substr(decoct(fileperms(POST('folder'))), 2, 3);
    if (file_exists('sql')) {
    	$existFolder = 'sql';
    } else if (file_exists('log')) {
    	$existFolder = 'log';
    }

?>


<script language="javascript">
function $(id) {
    return document.getElementById(id);
}
</script>
<style type="text/css">
DIV.title {background-color:#eee; padding:5px}
</style>

<form method="post" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" enctype="multipart/form-data">

  <div class="title">
  <input type="radio" name="action" value="export" id="export" checked="checked" /> <b><label for="export">Export</label></b>
  </div>

<div style="margin-top:10px">
  <div style="float:left">
      Type
      <select name="ex_type" class="export">
        <option value="textarea">textarea</option>
        <option value="zip" selected>zip архив</option>
        <option value="files">sql файлы в папку -> </option>
      </select>
      <input type="text" name="folder" value="<?php echo $existFolder?>" id="folder" class="export" />


      <br />
      <input type="checkbox" name="isStruct" value="1" id="isStruct" checked="checked" class="export" /> <b><label for="isStruct">Структура</label></b>
      <input type="checkbox" name="isData" value="1" id="isData" checked="checked" class="export" /> <b><label for="isData">Данные</label></b>
      <br />

      <input type="checkbox" name="query_list_fields" value="1" id="query_list_fields" checked="checked" /> <label for="query_list_fields">Указать список полей</label>
      <input type="checkbox" name="one_query" value="1" id="one_query" /> <label for="one_query">Инсерт одним запросом (меньше объем)</label><br>
      <input type="checkbox" name="ins_zadazd" value="1" id="ins_zadazd" /> <label for="ins_zadazd">DELAYED</label>
      <input type="checkbox" name="ins_ignore" value="1" id="ins_ignore" /> <label for="ins_ignore">IGNORE</label>
      
      <br />
    <label><input type="checkbox" name="forceExportByPart" value="1" />Принудительный экспорт по частям (в папку)</label>

  </div>

  <div>
  <select name="tables[]" multiple style="height:100px" class="export">
      <?php
      $result = mysql_list_tables($database);
      $c = 0;
      while ($row = mysql_fetch_row($result)) {
        $c++;
        echo ' <option selected>'.$row[0].'</option>';
      }
      ?>
      </select>
      <?php
      echo $c;
      ?>
      <script language="javascript">hh = 100</script>
      <a href="#" onclick="document.getElementsByTagName('select')[1].style.height=(hh+=100)+'px';return false;">≡</a>  </div>
</div>
<hr />




  <div class="title">
    <div><input type="radio" name="action" value="import" id="import" /> <b><label for="import">Import</label></b></div>
  </div>

<div style="margin-top:10px">
      Type
      <select name="type" class="import">
        <option value="files">sql файлы</option>
      </select>


из папки
<input type="text" name="ifolder" value="<?php echo $existFolder?>" id="ifolder" class="import" />

    <input class="import" type="file" name="file" />


</div>

<input type="checkbox" name="save_filled" value="1" id="save_filled" /> <label for="save_filled">Не перезаписывать заполненные таблицы</label>

<input type="checkbox" name="exitOnError" value="1" id="exitOnError" /> <label for="exitOnError">exit при первой ошибке</label>

<br>

Обрезать файл до размера (мб) <input type="text" name="cut_file" value="" id="cut_file" /><br>

Максимум запросов из 1 файла <input type="text" name="max_query" value="" id="max_query" /><br>

<input type="checkbox" name="log" value="1" id="log" /> <label for="log" checked="checked">Вести лог процесса в файл log.txt</label><br />
<input type="checkbox" name="import_type" value="slow" id="import_type" />
<label for="import_type">Уменьшить объем памяти (медленнее)</label><br />


Заменить в запросе это
<input type="text" name="replace_from" value="ENGINE=InnoDB" id="replace_from" />
на
<input type="text" name="replace_to" value="ENGINE=MyISAM" id="replace_to" />
<br>

<script language="javascript">
function addEvent(o, e, a) {
    if (o.addEventListener) {
    	o.addEventListener(e, a, false);
    }	else if (o.attachEvent) {
    	o.attachEvent("on" + e, a);
    }
}

var els = document.getElementsByClassName('export');
for (var i in els) {
    addEvent(els[i], 'click', function () {
        document.getElementById('export').checked = true;
    })
}
var els = document.getElementsByClassName('import');
for (var i in els) {
    addEvent(els[i], 'click', function () {
        document.getElementById('import').checked = true;
    })
}
</script>

  <div class="title">
      <script language="javascript">tt = 200</script>
      
  <input type="radio" name="action" value="sql" id="sql" /> <b><label for="sql">SQL запрос</label> <a href="#" onclick="document.getElementsByTagName('textarea')[0].style.height=(tt+=100)+'px';return false;">≡</a></b>
  </div>


  <textarea name="sql" style="width:98%; height:200px; margin-top:5px"></textarea>

  <?php

  echo implode('<br />', $lastSqls);

  ?>

  <hr />

	<div><input type="submit" value="Выполнить" /></div>
</form>
<?php
}




























//------------------------------------------------------------------------------------------------------------
//                                      Запрос
if (POST('action') == 'sql') {
    $sql = $_POST['sql'];
    if (!isset($_SESSION ['sql'])) {
    	$_SESSION ['sql'] = array();
    }
    if (mb_strlen($sql) < 200 && !in_array($sql, $_SESSION ['sql'])) {
    	$_SESSION ['sql'][]= $sql;
    	echo '<br /><pre>'.$sql.'</pre>';
    }
    if (preg_match('~^(select|show|explain) ~i', $sql)) {
        $a = round(array_sum(explode(" ", microtime())), 10);
    	echo printSqlTable($sql, $data=null);
    	$a = round(round(array_sum(explode(" ", microtime())), 10) - $a, 5);
        echo '<br />Time: '.$a;
    	exit;
    }
    
  //$content = stripslashes(POST('sql'));
    list($errors, $c, $affected) =
        execSql($sql, POST('import_type'), POST('replace_from'), POST('replace_to'), POST('max_query'));
    echo "<br />c=$c, affected=$affected";
    echo "<br />".mysql_error();
    if (is_array($errors)) {
        echo '<pre>';
    	echo htmlspecialchars(implode("\n", $errors));
    }
    exit;
}

















//------------------------------------------------------------------------------------------------------------
//                                      Импорт данных
if (POST('action') == 'import') {

    /**
     * Определяет, является ли контент в кодировке utf8
     */
    function isUtf8Codepage($content) {
        // ~[а-я]+~u (при условии, что файл в кодировке utf8) возвращает 1 на utf8
        // в остальных случаях возвращается 0
        return preg_match('~[а-я]+~u', $content) === 1;
    }
    
    function unpackZip($v, $folder) {
        echo '<br />'.$v.'... ';
        //echo basename($v, '.zip');
        //continue;
        $removeArchivedFiles = array();
        $zip = new PclZip($v);
        $list = $zip->extract($folder);
        if ($list) {
            echo ' распакован!';
            foreach ($list as $k => $v) {
                $removeArchivedFiles []= $v['filename'];
            }
        } else {
            echo 'ошибка распаковки... '.$zip->errorInfo(true);
        }
        return $removeArchivedFiles;
    }
    
    
    

    $removeArchivedFiles = array();
    $files = array();
    if ($_FILES && $_FILES['file']['tmp_name']) {
    	$tmpFile = $_FILES['file']['tmp_name'];
    	$name = $_FILES['file']['name'];
    	$type = $_FILES['file']['type'];
        if (strpos($name, '.sql') && !strpos($type, 'zip') === false) {
        	$files []= $tmpFile;
        } elseif (strpos($type, 'zip') !== false || strpos($type, '-tar') !== false) {
            $res = includePclZip();
            $removeArchivedFiles = $files = unpackZip($tmpFile, '.');
        } else {
        echo '<pre>'; print_r($_FILES); echo '</pre>';
            echo 'Это не архив "'.$name.'" и не sql файл';
            exit;
        }
    }

    if (POST('ifolder')) {
        $zips = glob(POST('ifolder').'/*.zip');
        if (count($zips) > 0) {
            echo '<p>Zips files found - '.count($zips).' items</p>';
            $res = includePclZip();
            if ($res) {
                foreach ($zips as $k => $v) {
                    $removeArchivedFiles = array_merge($removeArchivedFiles, unpackZip($v, POST('ifolder')));
                }
            }
        }
        $files = glob(POST('ifolder').'/*.sql');
        if (count($files) == 0) {
            exit('<br>No *.sql files on folder '.POST('ifolder'));
        }
    }

    if (count($files) == 0) {
        exit('<br>No *.sql files');
    }

    $data = '';
    foreach ($files as $key => $value) {
    
    //echo '<br />'.basename($value);

        $logStart = 'IMPORT file '.basename($value).' ... ';

        // Проверка таблицы
        if (POST('save_filled') == 1) {
            $tableName = basename($value, '.sql');
            $countRows = 0;
            $result = mysql_query('SELECT COUNT(*) AS c FROM `'.$tableName.'`');
            if ($result && $row = mysql_fetch_object($result)) {
                $countRows = $row->c;
            }
            if ($countRows > 0) {
                elog($logStart.' SKIP FILLED, rows='.$countRows);
                continue;
            }
        }

        // Проверка размера файла
        $size = round(filesize($value)/(1024*1024), 1);
        $content = file_get_contents($value);
        if (!isUtf8Codepage($content)) {
            exit($logStart."file $value is not in utf8");
        }

        if (POST('cut_file') > 0 && $size > POST('cut_file')) {
            echo $logStart.' cutted to filesize '.POST('cut_file').'mb (from '.$size.'mb)';
            $content = substr($content, 0, POST('cut_file')*1024*1024);
        }

        list($errors, $count, $affected) = execSql($content, POST('import_type'), POST('replace_from'), POST('replace_to'), POST('max_query'), POST('exitOnError'));
        $fault = count($errors);
        $succ = $count - $fault;
        if ($fault > 0) {
            elog("строк $count, запросов выполнено: $succ , неудач: $fault , затронуто рядов: $affected", 1);
            echo '<pre>'; print_r(array_unique($errors)); echo '</pre>';
        } else {
            elog($logStart." строк $count, ОК", 1);
        }
    }

    foreach ($removeArchivedFiles as $k => $v) {
        unlink($v);
    }

    exit;
}























if (POST('action') == 'export') {
//------------------------------------------------------------------------------------------------------------
//                                      Экспорт
$dumpHeader =
'-- '.MS_APP_NAME.' SQL Экспорт
--
-- Хост: '.$server.'
-- Время создания: '.date('j.m.Y, H-i').'
-- Версия сервера: '.getServerVersion().'
-- Версия PHP: '.phpversion().'
--
-- БД: `'.$database.'`
--

-- --------------------------------------------------------
';

if (POST('ex_type') == 'files') {
  if (!file_exists(POST('folder'))) {
    echo '<br>Не существует '.POST('folder').'';
    exit;
  }
  $perm = substr(decoct(fileperms(POST('folder'))), 2, 3);
  if ($perm != '777') {
    echo '<br>Права на папку '.POST('folder').' не равны 777';
    exit;
  }
}


$exp = new MySQLExport();
$exp->setComments(1);
$exp->setHeader($dumpHeader);
$exp->setOptionsStruct($addIfNot=true, $addAuto=true, $addKav=1);
$exp->setOptionsData(POST('query_list_fields', 0), POST('one_query', 0), POST('ins_zadazd', 0), POST('ins_ignore', 0));
// Экспорт БД
$exp->setDatabase($database);
$array = array();
$allow = false;
$tables = POST('tables');
//echo '<pre>';
//print_r($_POST);


// saveData2file($table, $data, 'zip')
function saveData2file($table, $data, $type='sql') {
  $filename = $table.'.'.$type;
  if (POST('folder') != '') {
    $filename = POST('folder').'/'.$filename;
  }
  echo "<br>EXPORT table '$table' in file '$filename' ... ";
  if (file_exists($filename)) {
    if (filesize($filename) > 0) {
      echo 'file exists!';
      return;
    }
    $f = fopen($filename, 'a+');
  } else {
    $f = fopen($filename, 'w+');
  }
  if (!$f) {
    echo 'ОШИБКА открытия файла, наверное нет прав';
    continue;
  }
    if ($type == 'zip') {
        $zip = new zipfile();
        $zip->addFile($data, $table.'.sql');
        $data = $zip->file();
    }
  if (!fwrite($f, $data)) {
    echo 'ОШИБКА записи в файл';
    return;
  }
	fclose($f);
	echo ' OK';
}


$isPartMode = false;
foreach ($tables as $table) {

  $exportfilename = $table.'.zip';
  if (POST('folder') != '') {
    $exportfilename = POST('folder').'/'.$exportfilename;
  }
  if (file_exists($exportfilename)) {
  	echo "<br>EXPORT table '$table' in file '$exportfilename' FILE EXISTS! Skip.... ";
  	continue;
  }

	$exp->setTable($table);

  if (POST('isStruct', 1)) {
    $exp->exportStructure($addDelim=true, $addDrop=false);
  }
  if (POST('isData', 0)) {
      if (isset($_POST['forceExportByPart'])) {
        saveData2file($table, $exp->data, 'zip');
        $exp->data = '';
        $exp->exportByPart($table, 0);
        continue;
      }
    $resultsCount = $exp->exportData('INSERT', $where=null);
    if (is_numeric($resultsCount)) {
        if (POST('ex_type') != 'files') {
            elog('Exported "'.$table.'" only '.$resultsCount.' rec - memory limit '.memory_get_usage()." > $memory_limit ...... Export by part", 1);
            echo '<h1>Не хватает памяти, экспортируйте данные в папку</h1>';
            exit;
        }
      elog('Exported "'.$table.'" only '.$resultsCount.' rec - memory limit '.memory_get_usage()." > $memory_limit ...... Export by part", 1);
      saveData2file($table, $exp->data, 'zip');
      $exp->data = '';
      $exp->exportByPart($table, $resultsCount);
      continue;
    }
  }

  if (POST('ex_type') == 'files') {
    saveData2file($table, $exp->data, 'zip');
  	$exp->data = '';
  }
}
if (POST('ex_type') != 'textarea') {
    echo '<br />EXPORT END------------';
}

if (POST('ex_type') == 'textarea') {
  ?>
  <html>
  <head>
  	<meta charset="utf-8" />
  	<title>Экспорт</title>
  </head>
  <body>
  <?php
  echo $exp->send();
} else if (POST('ex_type') == 'zip') {
  echo $exp->send('zip');
}

}
?>