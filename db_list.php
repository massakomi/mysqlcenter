<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
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
      $msc->addMessage('Ошибка', $sql, MS_MSG_FAULT, mysqli_errorx());
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
$showFullInfo = GET('mode') == 'full';

/**
 * Отображаем список баз данных с полной информацией
 */
if ($showFullInfo) {
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

/**
 * Отображаем список баз данных с краткой информацией
 */
} else {
    $hidden = array();
    if ($mscExists = in_array('mysqlcenter', $dbs)) {
        include_once 'includes/MSTable.php';
    	$hidden = MSTable::getHiddensArray();
    }
}

$pageProps = [
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
if (isajax()) {
    return $pageProps;
}

include_once(MS_DIR_TPL . 'db_list.htm.php');

