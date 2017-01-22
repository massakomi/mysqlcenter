<?php

$a = parse_url($_SERVER['REQUEST_URI']);
define('URL_ROOT', $a['path']);

require_once 'functions.php';

define('DB_HOST',       '127.0.0.1');
define('DB_USERNAME',   'test');
define('DB_PASSWORD',   'test');
define('DB_NAME',       'test');

global $conn;
$conn = pg_connect('hostaddr='.DB_HOST.' port=5432 dbname='.DB_NAME.' user='.DB_USERNAME.' password='.DB_PASSWORD);
if (!$conn) {
    echo 'Соединение не удалось';
    return ;
}

// echo '<pre>'; print_r(pg_version()); echo '</pre>';

//$data = getData('SELECT * FROM pg_stat_activity');
//echo '<pre>'; print_r($data); echo '</pre>';


?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8"/>
    <title>PG MSC</title>
    <link href="bootstrap/css/bootstrap.css" rel="stylesheet">
    <script src="bootstrap/js/bootstrap.min.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <style type="text/css">
    .table-pg {width:auto}
    .table-pg th, .table-pg td {
        padding: 3px!important;
        font-size: 12px;
    }
    .table-pg th {
        text-align: center;
        white-space: nowrap;
        background-color: #CCCCCC;
        color: #CC0000;
        border: 1px solid #000!important;
    }
    .table-pg td {
        border: 1px solid #ccc;
    }
    .table-pg tr:nth-child(even) {
        background-color: #eee;
    }
    .table-pg .glyphicon {
        font-size:16px;
    }
    .table-pg .glyphicon-edit {
        color:#006600
    }
    .table-pg .glyphicon-remove {
        color:#CC0000
    }
    .table-pg .p {
        padding:3px 5px!important;
    }
    .tbl-menu {padding: 0; margin-top:5px;}
    .tbl-menu li {list-style: none; line-height: 11px;}
    .tbl-menu li.tab a {margin-left:5px; color: #000000;}
    .tbl-menu a {font-size:10px; color: green;}
    .tbl-menu li.active a {color:red}
    form.top {vertical-align: top; display:inline-block; margin-bottom:10px;}
    form.top + .pagination {margin:0px;}

    .alert-sql {margin-bottom:10px; font-size: 12px; padding: 5px 10px;}
    </style>
</head><body>

  <nav class="navbar navbar-default navbar-static-top">
    <div class="container-fluid">
      <!-- заголовок навбара и меню для смартфона (меню важно)  -->
      <div class="navbar-header" style="width: 7.5%;">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="<?=URL_ROOT?>">PG MSC</a>
      </div>
      <div id="navbar" class="navbar-collapse collapse">
        <ul class="nav navbar-nav navbar-left">
        <?php
        $sru = $_SERVER['REQUEST_URI'];
        $t = $_GET['table'];
        if ($t) {
            $menu = [
                URL_ROOT.'?table='.$t => 'Обзор',
                URL_ROOT.'?table='.$t.'&s=tbl_struct' => 'Структура'
            ];
        } else {
            $menu = [
                'Базы данных',
                'Поиск',
                'Экспорт'
            ];
        }

        foreach ($menu as $k => $v) {
            $add = '';
            if ($k == '/') {
                if ($sru == $k) {
                	$add = ' class="active"';
                }
            } elseif ($k == $sru) {
            	$add = ' class="active"';
            }
        	echo '<li'.$add.'><a href="'.$k.'">'.$v.'</a></li>';
        }
        ?>
         </ul>
      </div>
    </div>
  </nav>

<div class="container-fluid" style="padding:0 10px;">

<div class="row">
    <div class="col-md-1">
    <?php

    $tables = listTables($onlyNames=true);

	$prefixes = array();
	foreach ($tables as $t) {
		$end = strlen($t) > 2 && strpos($t, '_', 3) > 0 ? strpos($t, '_', 3) : 50;
		$prefix = substr($t, 0, $end);
		$prefixes [$prefix] = !isset($prefixes [$prefix]) ? 1 : $prefixes [$prefix] + 1;
	}

    echo '<ul class="tbl-menu">';
    $addUrl = '';
    if ($_GET['s'] && $_GET['table']) {
    	$addUrl = '&s='.$_GET['s'];
    }
    foreach ($tables as $table) {
		$end = strlen($table) > 2 && strpos($table, '_', 3) > 0 ? strpos($table, '_', 3) : 50;
		$p = substr($table, 0, $end);
        $class = '';
		if (array_key_exists($p, $prefixes) && $prefixes[$p] > 1) {
			$class = 'tab';
		}
        if ($_GET['table'] == $table) {
        	$class .= ' active';
        }
        $add = '';
        if ($class) {
        	$add = ' class="'.$class.'"';
        }
        echo '<li'.$add.'><a href="?table='.$table.$addUrl.'">'.$table.'</a></li>';
    }
    echo '</ul>';
    ?>
    </div>
    <div class="col-md-11">
        <?php
        $s = $_GET['s'];
        if (!$s && $_GET['table']) {
        	$s = 'tbl_data';
        }
        if (!$s) {
        	$s = 'tbl_list';
        }
        include_once $s.'.php';
        ?>
    </div>
</div>


</div>
</body></html>