<?php

$a = parse_url($_SERVER['REQUEST_URI']);
define('URL_ROOT', $a['path']);

if ($_GET['db']) {
    setcookie('db', $_GET['db'], time() + 86400*365, '/');
}

require_once 'functions.php';

define('DB_HOST',       '127.0.0.1');
define('DB_USERNAME',   'test');
define('DB_PASSWORD',   'test');
define('DB_NAME',       $_GET['db'] ? $_GET['db'] : $_COOKIE['db']);

global $conn;
$s = 'hostaddr='.DB_HOST.' port=5432 user='.DB_USERNAME.' password='.DB_PASSWORD;
if (DB_NAME) {
	$s .= ' dbname='.DB_NAME;
}
$conn = pg_connect($s);
if (!$conn) {
    echo 'Соединение не удалось';
    return ;
}


?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8"/>
    <title>PG MSC</title>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <link href="data/css/bootstrap.css" rel="stylesheet">
    <script src="data/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="data/main.css" type="text/css" media="all" />
    <script type="text/javascript">
    $(document).ready(function(){
        $('.confirm').click(function() {
            if (!confirm('Подтвердите действие')) {
                return false;
            }
        })
        $('.prompt').click(function() {
            var def = $(this).attr('data-prompt');
            if (!def) {
            	def = $(this).html();
            }
            var val = prompt('Введите название', def)
            if (val === false) {
                return false;
            }
            $(this).attr('href', $(this).attr('href')+'&val='+val)
        })
    });
    </script>
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
                URL_ROOT.'?table='.$t.'&s=tbl_data' => 'Обзор',
                URL_ROOT.'?table='.$t.'&s=tbl_struct' => 'Структура'
            ];
        } else {
            $menu = [
                URL_ROOT.'?s=db_list' => 'Список баз',
                URL_ROOT => DB_NAME ? 'База '.DB_NAME : '',
                URL_ROOT.'?s=search' => 'Поиск',
                URL_ROOT.'?s=export' => 'Экспорт'
            ];
        }

        foreach ($menu as $k => $v) {
            if (!$v) {
                continue;
            }
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

<div class="container-fluid">

<div class="row">
    <div class="col-md-1">
    <?php

    function tableMenu()
    {
        $tables = listTables($onlyNames=true);

    	$prefixes = array();
    	foreach ($tables as $t) {
    		$end = strlen($t) > 2 && strpos($t, '_', 3) > 0 ? strpos($t, '_', 3) : 50;
    		$prefix = substr($t, 0, $end);
    		$prefixes [$prefix] = !isset($prefixes [$prefix]) ? 1 : $prefixes [$prefix] + 1;
    	}

        echo '<ul class="tbl-menu">';
        $addUrl = '&s=tbl_data';
        if (in_array($_GET['s'], array('tbl_data', 'tbl_struct')) && $_GET['table']) {
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
    }

    if (DB_NAME) {
        tableMenu();
    } else {
        err('База не выбрана');
    }

    ?>
    </div>
    <div class="col-md-11">
        <?php
        $s = $_GET['s'];
        if (!$s) {
        	$s = 'tbl_list';
        }
        include_once $s.'.php';
        ?>
    </div>
</div>


</div>



<?php
$version = pg_version();
?>
<footer class="footer">
  <div class="container-fluid">
    <p class="text-muted">client <?=$version['client']?> server <?=$version['server']?> кодировка <?=$version['server_encoding']?> имя юзера <?=$version['session_authorization']?></p>
  </div>
</footer>

</body></html>