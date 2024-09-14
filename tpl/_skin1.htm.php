<?php /* @var $this PageLayout */ ?>
<?php /* @var $msc MSCenter */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo $msc->getWindowTitle()?></title>
    <script language="javascript" src="<?php echo MS_DIR_JS?>jquery-2.2.4.min.js"></script>
    <script type="text/javascript" language="javascript">
    /*$.noConflict();*/
    </script>
    <script language="JavaScript" src="<?php echo MS_DIR_JS?>MysqlCenter.js?<?=filemtime(MS_DIR_JS.'MysqlCenter.js')?>"></script>
    <script language="javascript">
    var debug = '1';
    </script>
    <link rel="stylesheet" type="text/css" href="<?php echo MS_DIR_CSS?>page.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo MS_DIR_CSS?>color.white.css" />
    <link rel="shortcut icon" href="/favicon.ico"/>

    <!-- Note: when deploying, replace "development.js" with "production.min.js". -->
    <script src="/js/react.development.js" crossorigin></script>
    <script src="/js/react-dom.development.js" crossorigin></script>
    <script src="/js/react-babel.min.js"></script>
    <script type="text/babel" src="/js/components.js"></script>
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
        <span class="hiddenText" onclick="showhide(get('queryPopupBlock')); return false">запросы&nbsp;</span>
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
    <td><div id="msAjaxQueryDiv"></div></td>
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
  &nbsp; &nbsp; &nbsp;<a href="?s=test">test</a>
  <strong>Хост:</strong> <?php echo DB_HOST ?> &nbsp;&nbsp;
  <strong>Пользователь:</strong> <?php echo DB_USERNAME_CUR ?> &nbsp;&nbsp;
<?php
if (function_exists('memory_get_peak_usage')) {
?>
  пиковая память <?php echo formatSize(memory_get_peak_usage()) ?> &nbsp;
  сейчас <?php echo formatSize(memory_get_usage()) ?> &nbsp;
  inc <?php echo formatSize(array_sum(array_map(fn($file) => filesize($file), get_included_files())))  ?>
  limit <?php echo ini_get('memory_limit') ?> &nbsp; &nbsp;&nbsp;
<?php
}
?>$
  <strong><a href="?s=logout">Выход</a></strong>
</div>

<script type="text/javascript">
  mysqlCenterInit()
</script>
</html>