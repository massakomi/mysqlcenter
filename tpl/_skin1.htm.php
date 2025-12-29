<?php
/* @var $msc MSCenter */
/* @var $umaker UrlMaker */
global $umaker, $msc;
$menu = new Menu();
$fields = $msc->table ? DatabaseTable::getFields($msc->table, true) : [];
$dbs = Server::getDatabasesWithoutHidden();
?>
<!DOCTYPE>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title><?php echo $msc->getWindowTitle() ?></title>
    <script language="JavaScript" src="/js/MysqlCenter.js?<?= filemtime(MS_DIR_JS . 'MysqlCenter.js') ?>"></script>
    <script language="javascript">
        let debug = '1';
        window.fields = <?=json_encode($fields) ?>;
    </script>
    <link rel="stylesheet" type="text/css" href="<?php echo MS_DIR_CSS ?>page.css"/>
    <link rel="shortcut icon" href="/favicon.ico"/>

    <!-- Note: when deploying, replace "development.js" with "production.min.js". -->
    <script src="/js/lib/react.development.js" crossorigin></script>
    <script src="/js/lib/react-dom.development.js" crossorigin></script>
    <script src="/js/lib/react-babel.min.js"></script>
    <script src="/js/components.js" type="text/babel"></script>
</head>
<body>
<div class="loader" hidden></div>
<div class="pageBlock">
    <b id="appNameId"><a href="?db_list">MySQL React</a></b> &nbsp; &nbsp;
    <?php echo $menu->getGlobalMenu() ?> &nbsp; &nbsp;
    <span class="hiddenText" onclick="sqlFormToggle()"
          title="Кликните, чтобы открыть форму быстрого запроса"><?php echo $time ?> с. &nbsp;&nbsp;  </span>
    <span class="menuChain"><?php echo $menu->getChainMenu() ?></span>
</div>

<div id="msAjaxQueryDiv"></div>
<div id="errorMessage"></div>

<div class="outerTable">
    <div class="leftCol">
        <?php echo $menu->getTableMenu(); ?>
    </div>
    <div class="rightCol">
        <div class="headTop">
            <h1><?php echo $msc->getPageTitle() ?></h1>
            <div>
                <?php
                if ($msc->table) {
                    $url = '?db=' . $msc->db . '&table=' . $msc->table . '&s=tbl_data';
                    ?>
                    <form action="<?php echo $url ?>" method="post" class="search-top">
                        <input type="hidden" name="order" value="<?= POST('order') ?>"/>
                        <input type="hidden" name="go" value="<?= POST('go') ?>"/>
                        <input type="text" name="query"
                               value="<?= htmlspecialchars(POST('query', 'Поиск или where')) ?>"/>
                        <?= $menu->selector($fields, ' name="field"', POST('field'), '', false) ?>
                        <?= $menu->selector(['=', 'like'], ' name="like"', POST('like'), '', false) ?>
                        <input type="text" name="byField" value="<?= POST('byField') ?>"/>
                        <input type="submit">
                    </form>
                    <?php
                }
                if ($msc->db) {
                    ?>
                    <form action="?db=<?= $msc->db ?>&s=search" method="post" style="display:inline"
                          onsubmit="this.action=this.action+'&query='+this.query.value">
                        <input type="text" name="query" value="Поиск по базе"
                               onfocus="this.value=''; this.style.width='auto'" style="width: 100px"/>
                    </form>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php echo $contentMain ?>
    </div>
</div>

<form action="<?php echo $umaker->make('s', 'sql') ?>" onsubmit="sqlFormSubmit()" class="popupGeneralForm tableFormEdit" method="post">
    <input type="submit" value="Отправить запрос!"/>
    <textarea name="sql" rows="15" wrap="soft"><?= POST('sql') ?></textarea>
    <span></span>
    <a href="#" onclick="sqlFormToggle(); return false">закрыть</a>
</form>

<div class="menuDb">
    <?php
    foreach ($dbs as $db) {
        echo '<a href="?db=' . $db . '">' . $db . '</a>';
    }
    ?>
</div>

<div class="pageBlock">
    <div class="globalMenu">
        <a href="?s=config">Настройки</a>
        <a href="?s=test">test</a>
        <a href="?s=login">Логин</a>
    </div>
    <strong>Хост:</strong> <?php echo $msc->host ?> &nbsp;&nbsp;
    <strong>Пользователь:</strong> <?php echo $msc->user ?> &nbsp;&nbsp;
    <?php if (function_exists('memory_get_peak_usage')) { ?>
        пиковая память <?php echo Utils::formatSize(memory_get_peak_usage()) ?> &nbsp;
        сейчас <?php echo Utils::formatSize(memory_get_usage()) ?> &nbsp;
        inc <?php echo Utils::formatSize(array_sum(array_map(fn($file) => filesize($file), get_included_files()))) ?>
        limit <?php echo ini_get('memory_limit') ?> &nbsp; &nbsp;&nbsp;
    <?php } ?>
</div>

</html>