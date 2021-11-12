<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

/**
 * SQL запрос в БД
 */

$mysql_charsets = getCharsetArray();

$msc->pageTitle = 'SQL запрос в БД';




/*
function readSqlFilePart($filename, $part, $from) {

  echo "<br><br>Read from=".round($from/1024/1024,2)."mb length=$part";
  $fd = fopen ($filename, "r");
  echo "<br>fseek $from";
  fseek($fd, $from);
  echo "<br>fread $part";
  $contents = fread ($fd, $part);
  $pos = strrpos($contents, ";\n");
  if ($pos === false) {
    //echo $contents;
    echo ";n not found [$filename, $part, $from]";
    return array(false, false);
  }
  $pos += 2;
  echo "<br>*pos = $pos";

  fseek($fd, $from);
  $contents = fread ($fd, $pos);
  echo "<br>*fseek $from";
  echo "<br>*fread $pos";
  //echo '<br><br><br>'.$contents;

  fclose($fd);
  return array($contents, $from + $pos);

}

$pos = 0;
//echo '<pre>';

for ($i = 0; $i < 50; $i ++) {
  list($part, $pos) = readSqlFilePart('Z:/marketmixer2.sql', 1024*1024*100, $pos);
  if ($part === false) {
    break;
  }
  $part = str_replace('ENGINE=InnoDB ', 'ENGINE=MyISAM ', $part);
  //echo '<hr>';
  execSql(GET('db'), &$part);
  //$part = nl2br($part); echo $part;
  //echo '<span style="color:red">]'.$i.'-'.$pos.'[</span>';
}


// 	execSql(GET('db'), &$s);

return;
*/
// Запрос из файла
if (isset($_FILES['sqlFile']) && $_FILES['sqlFile']['size'] > 0) {
	if ($_FILES['sqlFile']['size'] <= MAX_UPLOAD_SIZE) {
		if (POST('compress') == 'zip' || substr($_FILES['sqlFile']['name'], -3) == 'zip') {
            if (!function_exists('zip_open')) {
            	$msc->addMessage('Расширение zip не включено. Не могу распаковать файл', null, MS_MSG_FAULT);
            	return null;
            }
			$zip = zip_open($_FILES['sqlFile']['tmp_name']);
			if ($zip) {
				$s = null;
				while ($zip_entry = zip_read($zip)) {
					if (zip_entry_open($zip, $zip_entry, "r")) {
						$s .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
						zip_entry_close($zip_entry);
					}
				}
				zip_close($zip);
			}

        } else if (POST('compress') == 'csv') {

            $fields = getFields('');

            $data = file_get_contents($_FILES['sqlFile']['tmp_name']);
            $data = iconv('windows-1251', 'utf-8', $data);
			$data = explode("\n", $data);
            if (0) {
            	$data = array_unique($data);
            }
            foreach ($data as $k => $v) {
            	$row = array_map('mysqli_escape_stringx', explode(';', trim($v)));
                echo '<br />INSERT INTO s_products_text (brand, name) VALUES ("'.implode('","', $row).'");';
            }

        } else if (POST('compress') == 'excel') {

            include_once 'includes/excel_reader.php';

            $data = new Spreadsheet_Excel_Reader($_FILES['sqlFile']['tmp_name'], false);

            foreach ($data->boundsheets as $k => $v) {
            	echo '<a href="?sheet='.$k.'">'.$v['name'].'</a> &nbsp; ';
            }
            // excel_reader.php

            $sheet = 1;

            echo '<br /><br />';
        	//echo '<pre>'; print_r($data->sheets[$_GET['sheet']]); echo '</pre>';
        	//$sheet = array();

            echo '
            <style>
            TABLE.optionstable {empty-cells:show; border-collapse:collapse;}
            TABLE.optionstable TH {background-color: #eee}
            TABLE.optionstable TH, TABLE.optionstable TD {border:1px solid #ccc; padding: 2px 4px; vertical-align: top;}
            </style>
            <table class="optionstable">';
            foreach ($data->sheets[$sheet]['cells'] as $cell => $values) {
            	//$sheet [][]= ;
            	$str = trim(implode(' ', $values));
                if (empty($str)) {
                	continue;
                }
                echo '<tr>';
                //array_shift($values);
                foreach ($values as $k => $v) {
            	   echo '<td>'.$v.'</td>';
                }
            	echo '</tr>';
            }
            echo '</table>';

            unset($data->sheets[$sheet]['cells']);

            echo '<pre>'; print_r($data->sheets[$sheet]); echo '</pre>';



		} else {
			$mime = '';
			if (POST('compress') == '') {
				$mime = 'text/plain';
			} else if (POST('compress') == 'gzip') {
				$mime = 'application/x-gzip';
			}
			$s = readZipFile($_FILES['sqlFile']['tmp_name'], $mime);
		}
		// Применяем кодировку если надо
		if (POST('sqlFileCharset') != null && POST('sqlFileCharset') != 'utf8') {
            $msc->query("SET NAMES '".POST('sqlFileCharset')."'");
		}
		//echo substr_count($s, 'ENGINE=InnoDB');
		$s = str_replace('ENGINE=InnoDB', 'ENGINE=MyISAM', $s);
		//echo '-'.substr_count($s, 'ENGINE=InnoDB');
		//exit; 
		$log = strlen($s) < 10000;
		execSql(GET('db'), $s, $log);
		if (POST('sqlFileCharset') != null && POST('sqlFileCharset') != 'utf8') {
            $msc->query("SET NAMES 'utf8'");
		}
	} else {
		$msc->addMessage('Размер файла превышает максимально допустимый', null, MS_MSG_FAULT);
	}
// Запрос из ПОСТа
} else if (isset($_POST['sql']) && $_POST['sql'] != '') {
	$a = ini_get("magic_quotes_gpc");
	if (is_numeric($a)) {
		$_POST['sql'] = stripslashes($_POST['sql']);
	}
	if (trim(mb_strtolower(substr($_POST['sql'], 0, 6))) == 'select') {
		// редирект на лист
		$msc->pageTitle = 'Обзор таблицы ';
		$msc->page = 'tbl_data';
		$directSQL = $_POST['sql'];
		include_once(DIR_MYSQL . 'tbl_data.php');
		return null;
	} else {
        $log = strlen($_POST['sql']) < 10000;
		execSql(GET('db'), $_POST['sql'], $log);
	}
}

// Вывод
$charsets = plDrawSelector($mysql_charsets, ' name="sqlFileCharset"', 'utf8', '		');
include(MS_DIR_TPL . 'sql.htm.php');
?>