<?php
header("Content-Type: text/html; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8" ?>'."\n";?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo $msc->getWindowTitle()?></title>
    <script language="javascript" src="<?php echo MS_DIR_JS?>jquery-1.7.1.min.js"></script>
    <script type="text/javascript" language="javascript">
    $.noConflict();
    window.onerror = function (e) {
        //alert('Ошибка: ' + arguments[0] + '\r\nСтрока: ' + arguments[2] + '\r\nФайл: ' + arguments[1])
    }
    </script>
    <script language="JavaScript" src="<?php echo MS_DIR_JS?>FrameWork.js"></script>
    <script language="JavaScript" src="<?php echo MS_DIR_JS?>MysqlCenter.js"></script>
    <script language="JavaScript" src="<?php echo MS_DIR_JS?>XLAjax.js"></script>
    <script language="javascript">
    var debug = '1';
    var xla = new XLAjax('ajax.php');
    </script>
    <link rel="stylesheet" type="text/css" href="<?php echo MS_DIR_CSS?>page.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo MS_DIR_CSS?>color.white.css" />
    <link rel="shortcut icon" href="/favicon.ico"/>
</head>
<body>
<div class="pageBlock">
  <b id="appNameId"><a href="?db_list"><?php echo MS_APP_NAME?></a></b> &nbsp; &nbsp; 
<?php echo $this->getGlobalMenu()?> &nbsp; &nbsp;
  <span class="hiddenText" onclick="msDisplaySql()" title="Кликните, чтобы открыть форму быстрого запроса"><?php echo round(round(array_sum(explode(" ", microtime())), 10) - $msc->timer, 5) ?> с. &nbsp;&nbsp;  </span>
  <span class="menuChain"><?php echo $this->getChainMenu()?></span>
</div>
<table width="100%" class="outerTable">
  <tr>
    <td width="100" class="tableMenuTd">
<?php
echo $this->getTableMenu();

?>
      <img src="tpl/images/spacer.png" width="100" height="1" />
    </td>
    <td>
      <table width="800" border=0 cellspacing=0 cellpadding=0><tr>
        <td width="500"><h1><?php echo $msc->getPageTitle()?></h1></td>
        <td style="white-space:nowrap">
        <span class="hiddenText" onclick="showhide($('queryPopupBlock')); return false">запросы&nbsp;</span>
        <?php echo count($msc->queries) ?>
        &nbsp;&nbsp;
        <?php
        if (GET('table') != '') {
            $url = '?db='.$msc->db.'&table='.$msc->table.'&s=tbl_data';
        ?>
        <form action="<?php echo $url?>" method="post" style="display:inline" onsubmit="this.action=this.action+'&query='+this.query.value">
        <input type="text" name="query" value="Поиск по таблице" onfocus="this.value=''" />
        </form>
        <?php
        }
        $url = '?db='.$msc->db.'&s=search';
        ?>
        <form action="<?php echo $url?>" method="post" style="display:inline" onsubmit="this.action=this.action+'&query='+this.query.value">
        <input type="text" name="query" value="Поиск по базе" onfocus="this.value=''" />
        </form>
        </td></tr>
        
    </table>
<?php
if (conf('showmessages') == '1') {
    echo $msc->getMessages();
}
?>
<?php echo $contentMain?>
    </td>
    <td><div id="msAjaxQueryDiv">&nbsp;</div></td>
  </tr>
</table>
<form action="<?php echo $umaker->make('s', 'sql') ?>" class="popupGeneralForm tableFormEdit" method="post" name="sqlPopupQueryForm" id="sqlPopupQueryForm" style="text-align:right">
  <input type="submit" value="Отправить запрос!" />
  <textarea name="sql" rows="15" wrap="off"></textarea>
  <a href="#" onclick="msDisplaySql(); return false">закрыть</a>
</form>
<div id="dbHiddenMenu">
<?php
$dbs = Server::getDatabases();
$hidden = array();
if (in_array('mysqlcenter', $dbs)) {
    include_once 'includes/MSTable.php';
	$hidden = MSTable::getHiddensArray();
}

foreach ($dbs as $db) {
    if (in_array($db, $hidden)) {
    	continue;
    }
	echo '<a href="?db='.$db.'">'.$db.'</a><br />';
}
?>
</div>
<div id="queryPopupBlock">
<?php
foreach ($msc->queries as $query) {
	echo $query.'<br />';
}
?>
</div>

<div class="pageBlock">  
	<?php echo $this->getFooterMenu()?> &nbsp;&nbsp;&nbsp;
  &nbsp; &nbsp; &nbsp;
  <strong>Хост:</strong> <?php echo DB_HOST ?> &nbsp;&nbsp;
  <strong>Пользователь:</strong> <?php echo DB_USERNAME_CUR ?> &nbsp;&nbsp;
<?php
if (function_exists('memory_get_peak_usage')) {
?>
  пиковая память <?php echo formatSize(memory_get_peak_usage()) ?> &nbsp;
  сейчас <?php echo formatSize(memory_get_usage()) ?> &nbsp;
  inc <?php echo formatSize(array_sum(array_map(create_function('$file', 'return filesize("$file");'), get_included_files())))  ?>
  limit <?php echo ini_get('memory_limit') ?> &nbsp; &nbsp;&nbsp;
<?php
}
?>
  <strong><a href="?s=logout">Выход</a></strong>
</div>
<script language="javascript">
hideTimeout = null;
$('appNameId').onmouseover = function () {
	$('dbHiddenMenu').style.display = 'block';
}
function menuHidder(e) {
  var w = parseInt(jQuery('#dbHiddenMenu').width());
  if (e.pageX > w) {
  	hideTimeout = setTimeout(function() {
  		$('dbHiddenMenu').style.display = 'none';
  	}, 300);
  }
}

$('dbHiddenMenu').onmouseout = menuHidder;

$('dbHiddenMenu').onmouseover = function (e) {
	if (hideTimeout != null) {
		clearInterval(hideTimeout);
	}
}
$('dbHiddenMenu').onclick = function (e) {
  $('dbHiddenMenu').style.display = 'none';
}
$('queryPopupBlock').style.display = 'none'



// Определяет активность клавиши CTRL
var globalCtrlKeyMode = false;
var key = {
	needkey:function(e) {
		var code;
		if (!e) var e = window.event;
		if (e.keyCode) code = e.keyCode;
		else if (e.which) code = e.which;
        if (globalCtrlKeyMode == true) {
            globalCtrlKeyMode = false;
        }
		if (e.ctrlKey == true && e.type == 'keydown') {
            globalCtrlKeyMode = true;
        }
	}
}
if (document.getElementById) {
	document.onkeydown = key.needkey;
	document.onkeyup = key.needkey;
}

// Мультиселектор чекбоксов. Указать индекс чекбокса и селектор элемента где он находится
// <input name="table[]" type="checkbox" value="1" onclick="checkboxer(5, '#row');">
var globalCheckboxLastIndex = null;
function checkboxer(index, selector) {
    if (globalCheckboxLastIndex == null) {
    	globalCheckboxLastIndex = index;
    	//return true;
    } else if (globalCtrlKeyMode && index != globalCheckboxLastIndex) {
    	var from = globalCheckboxLastIndex > index ? index : globalCheckboxLastIndex;
    	var to = globalCheckboxLastIndex > index ? globalCheckboxLastIndex : index;
        // Добавляем класс если надо
    	var addClass = null;
        if (jQuery(selector+from).hasClass('selectedRow') || jQuery(selector+to).hasClass('selectedRow')) {
        	var addClass = 'selectedRow';
        }
        for (var i = from; i <= to; i ++) {
            var o = jQuery(selector+i+' input');
           	o.attr('checked', true);
            if (addClass != null) {
            	jQuery(selector+i).addClass(addClass);
            }
        }
    }
}
</script>
</html>