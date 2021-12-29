<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

/**
 * Отображение списка баз данных
 */

if (!defined('DIR_MYSQL')) { 
    exit('Hacking attempt');
}

if (GET('mode') == 'speedtest') {
  $msc->pageTitle = 'Тест скорости';
  
  //$start = round(round(array_sum(explode(" ", microtime())), 10) - $msc->timer, 5);
  
  $start = round(array_sum(explode(" ", microtime())), 10);
  
  function tquery($sql) {
    global $msc;
    $result = $msc->query($sql);
    if (!$result) {
      $msc->addMessage('Ошибка', $sql, MS_MSG_FAULT, mysqli_error());
    }
  }
  
  //$msc->addMessage('Создаем базу test', '', MS_MSG_SIMPLE);
  $sql = 'CREATE DATABASE `test`';
  tquery($sql);
  
  $msc->selectDb('test') or die('oooops');

  $msc->allowRepeatMessages = true;

  for ($i = 0; $i < 5; $i ++) {
    $tname = '`test'.$i.'`';
    //$msc->addMessage('Создаем таблицу '.$tname.'', '', MS_MSG_SIMPLE);
    $sql = '
    CREATE TABLE '.$tname.' (
      `id` INT NOT NULL AUTO_INCREMENT,
      `content` VARCHAR(255),
      `title` VARCHAR(200) NOT NULL,
      PRIMARY KEY (id)
    )';
    tquery($sql);
    //$msc->addMessage('Добавляем 10 рядов <span>', '', MS_MSG_SIMPLE);
    for ($j = 1; $j <= 10; $j ++) {
      $sql = 'INSERT INTO '.$tname.' VALUES ('.$j.', "content'.$j.'", "content'.$j.'")';
      tquery($sql);
    }
  }

  //$msc->addMessage('Удаляем базу test', '', MS_MSG_SIMPLE);
  $sql = 'DROP DATABASE `test`';
  $msc->query($sql);
  
  echo '<h1>Тест скорости запросов</h1>';
  $point = round(array_sum(explode(" ", microtime())), 10);
  echo $test1 = round($point - $start, 2);
  
  echo '<h1>Тест выполнения php</h1>';
  $a = 1;
  for ($i = 0; $i < 1000000; $i ++) {
  	$a *= 2 + $a + rand(1,2);
  }
  echo $test2 = round(round(array_sum(explode(" ", microtime())), 10) - $point, 2);
  
  
  $f = fopen('test.txt', 'a+');
  fwrite($f, "\n".date('Y.m.d H:i:s')." sql=$test1 php=$test2");
  fclose($f);
  
  
  return;
}

// Получаем массив баз данных
$dbs = Server::getDatabases();

$msc->pageTitle = 'Список баз данных сервера ' . DB_HOST . ' (всего: '.count($dbs).')';
$table = new Table('contentTable', null, null, null, null, 'structureTableId');

// Определяем, показывать ли полную информацию или нет
$showFullInfo = ((MS_DB_FULL_INFO && !isset($_GET['mode'])) || GET('mode') == 'full');

/**
 * Отображаем список баз данных с полной информацией
 */
if ($showFullInfo) {
    //$dbs = array_slice($dbs, 0, 1);
    foreach ($dbs as $j => $db) {
        $dbItem = [
            'name' => $db,
            'extra' => []
        ];
        $result = $msc->query('SHOW TABLE STATUS FROM `' . $db . '`');
        if ($result) {
            while ($row = mysqli_fetch_object($result)) {
                $dbItem ['extra'][]= $row;
            }
        }
        $dbs [$j] = $dbItem;
    }


    /*
    $table->makeRowHead('&nbsp;', 'Название', '&nbsp;', 'Таблиц', 'Обновлено', 'Рядов', 'Размер');
    $countTotalTables = $countTotalSize = $countTotalRows = 0;
    foreach ($dbs as $j => $db) {
        $idRow = "db$db";
        $ms = "msQuery('dbDelete', 'db=$db&id=$idRow'); return false";
        $result = $msc->query('SHOW TABLE STATUS FROM `'.$db.'`');
        $countTables = $countSize = $countRows = 0;
        $updateTime = null;
        if ($result) {
            while ($row = mysqli_fetch_object($result)) {
                $countTables ++;
                if ($row->Update_time > 0) {
                    $updateTime = max($updateTime, strtotime($row->Update_time));
                }
                $countRows += $row->Rows;
                $countSize += MSC_roundZero(($row->Data_length + $row->Index_length) / 1024, 1);
            }        
        }
        $countTotalTables += $countTables;
        $countTotalSize   += $countSize;
        $countTotalRows   += $countRows;    
        if ($updateTime != null) {
            $updateTime = date2rusString(MS_DATE_FORMAT, $updateTime);
            if (strpos($updateTime, 'дня') !== false || strpos($updateTime, 'ера') !== false) {
                $updateTime = "<b>$updateTime</b>";
            }
        }
        $table->makeRow(array(
            '<input name="databases[]" type="checkbox" value="'.$db.'" class="cb">',
            '<a href="'.$umaker->make('db', $db, 's', 'tbl_list').'" title="Структура БД" id="'.$idRow.'">'.$db.'</a>',        
            '<a href="#" onclick="'.$ms.'" title="Удалить '.$db.'"><img src="'.MS_DIR_IMG.'close.png" alt="" border="0" /></a>',
            $countTables,
            $updateTime,
            $countRows,
            number_format($countSize, 1)), '', '', 
            array(
                '',
                '',
                '',
                ' style="text-align:right"',
                '',
                ' style="text-align:right"',
                ' style="text-align:right"'
            )
        );
    }
    $table->makeRow(
        '',
        '',        
        '',
        $countTotalTables,
        '',
        $countTotalRows,
        $countTotalSize > 1024 ? round($countTotalSize/1024, 1).' мб' : $countTotalSize.' кб'
    );
    */

/**
 * Отображаем список баз данных с краткой информацией
 */
} else {
    //$table->makeRowHead('&nbsp;', 'Название', '&nbsp;');

    $hidden = array();
    if ($mscExists = in_array('mysqlcenter', $dbs)) {
        include_once 'includes/MSTable.php';
    	$hidden = MSTable::getHiddensArray();
    }

    /*foreach ($dbs as $j => $db) {
        $st = '';

        $idRow = "db$db";
        $ms  = "msQuery('dbDelete', 'db=$db&id=$idRow'); return false";
        $add = '';
        if ($mscExists) {
            if (in_array($db, $hidden)) {
            	$st = ' style="color:#ccc"';
            	$add = '&action=show';
            }
        	$add = ' &nbsp; <a href="#" onclick="'."msQuery('dbHide', 'db=$db&id=$idRow$add'); return false".'" title="Спрятать/показать '.$db.'"><img src="'.MS_DIR_IMG.'open-folder.png" alt="" border="0" width=16 /></a>';
        }
        $table->makeRow(
            '<input name="databases[]" type="checkbox" value="'.$db.'" class="cb">',
            '<a href="'.$umaker->make('db', $db, 's', 'tbl_list').'" title="Структура БД" id="'.$idRow.'"'.$st.'>'.$db.'</a>',
			// Добавляем в комментарий имя БД, чтобы в автотестах определить, куда кликать при проверке удаления
            '<a href="#" onclick="'.$ms.'" title="Удалить '.$db.'"><img src="'.MS_DIR_IMG.'close.png" alt="" border="0" /></a> &nbsp; <a href="'.$umaker->make('db', $db, 's', 'actions').'" title="Изменить"><img src="'.MS_DIR_IMG.'edit.gif" alt="" border="0" /></a>'.$add
			
        );
    }*/
}

if (isajax()) {
    return [
        'databases' => $dbs,
        'hiddens' => $hidden,
        'appName' => MS_APP_NAME,
        'appVersion' => MS_APP_VERSION,
        'dbHost' => DB_HOST,
        'showFullInfo' => $showFullInfo,
        'folder' => MS_DIR_IMG,
        'url' => MS_URL,
        'dbname' => $msc->db,
        'phpversion' => phpversion(),
        'mysqlVersion' => PMA_MYSQL_STR_VERSION,
    ];
}

include_once(MS_DIR_TPL . 'db_list.htm.php');

/*
    let databases = <?=json_encode($dbs)?>;
    let hiddens = <?=json_encode($hidden)?>;

    ReactDOM.render(
        <App appName="<?php echo MS_APP_NAME ?>" appVersion="<?php echo MS_APP_VERSION ?>" dbHost="<?php echo DB_HOST ?>" showFullInfo="<?=$showFullInfo?>" folder="<?=MS_DIR_IMG?>" url="<?=MS_URL?>"
            dbname="<?php echo $msc->db ?>" phpversion="<?=phpversion()?>" mysqlVersion="<?=PMA_MYSQL_STR_VERSION?>" />,
        document.getElementById('root')
    );

 * */
?>