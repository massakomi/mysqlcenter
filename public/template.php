<?php

declare(strict_types=1);

use database\Server;
use database\Table;
use service\Menu;
use service\PageLayout;
use service\UrlMaker;
use service\Utils;

global $msc;

$pageProps = (new PageLayout())->execute();
$time = round(round(array_sum(explode(" ", microtime())), 10) - $msc->timer, 5);
$menu = new Menu();
$fields = $msc->table && $msc->db ? Table::getFieldNames($msc->table) : [];
$databases = Server::getDatabases();
$databasesVisible = Server::getDatabasesWithoutHidden();

?>
<!DOCTYPE>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title><?php echo $msc->getWindowTitle() ?></title>
    <link rel="stylesheet" type="text/css" href="/css/page.css?<?=filemtime('css/page.css')?>"/>
    <link rel="shortcut icon" href="/favicon.ico"/>
    <script defer src="/js/dist.js?<?=filemtime('js/dist.js')?>"></script>
</head>
<body>
<div class="loader" hidden></div>
<div class="pageBlock">
    <a class="appName" href="?db_list"><?=$msc->driverName == 'pgsql' ? 'PgSQL' : 'MySQL'?> React</a>
    <?php echo $menu->getGlobalMenu() ?>
    <span class="hiddenText sqlFormToggle"
          title="Кликните, чтобы открыть форму быстрого запроса"><?php echo $time ?> с.</span>
    <span class="menuChain"><?php echo $menu->getChainMenu() ?></span>
    <div class="globalMenu menuTopRight">
        <a href="#" id="add-task">Task</a>
        <a href="?s=config">Настройки</a>
        <a href="?s=login">Логин</a>
    </div>
</div>

<div id="msAjaxQueryDiv"></div>
<div id="errorMessage"></div>

<div class="outerTable">
    <div class="leftCol">
        <?php echo $menu->getTableMenu(); ?>
    </div>
    <div class="rightCol">
        <div id="root"></div>
    </div>
</div>

<form action="<?php echo UrlMaker::make('s', 'sql') ?>" class="popupGeneralForm tableFormEdit" method="post">
    <input type="submit" value="Отправить запрос!"/>
    <textarea name="sql" rows="15" wrap="soft"><?= POST('sql') ?></textarea>
    <span></span>
    <a href="#">закрыть</a>
</form>

<div class="menuDb">
    <?php
    foreach ($databasesVisible as $db) {
        echo '<a href="?db=' . $db . '">' . $db . '</a>';
    }
    ?>
</div>

<div class="pageBlock bottom">
    <span><strong>Driver:</strong> <?php echo $msc->driverName ?></span>
    <span><strong>Db:</strong> <?php echo $msc->db ?></span>
    <span><strong>Host:</strong> <?php echo $msc->host ?></span>
    <span><strong>User:</strong> <?php echo $msc->user ?></span>
    <span class="hiddenText">пиковая память <?php echo Utils::formatSize(memory_get_peak_usage()) ?></span>
    <span class="hiddenText">сейчас <?php echo Utils::formatSize(memory_get_usage()) ?></span>
    <span class="hiddenText">inc <?php echo Utils::formatSize(array_sum(array_map(fn($file) => filesize($file), get_included_files()))) ?></span>
    <span class="hiddenText">limit <?php echo ini_get('memory_limit') ?></span>
</div>

<script>
    window.driver = '<?=$msc->driverName?>';
    window.component = '<?=$msc->page?>'
    window.db = '<?=$msc->db?>'
    window.table = '<?=$msc->table?>'
    window.user = '<?=$msc->user?>'
    window.pageTitle = '<?=$msc->getPageTitle()?>'
    window.databases = <?=json_encode($databases) ?>;
    window.fields = <?=json_encode($fields) ?>;
    window.options = <?=json_encode($pageProps)?>;
    window.messages = <?=json_encode($msc->getMessagesData(), JSON_INVALID_UTF8_IGNORE)?>;
    window.messagesHide = <?=config('hideMessages')?>;
    window.post = <?=json_encode($_POST)?>;
</script>
</body>
</html>