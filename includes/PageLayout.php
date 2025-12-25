<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Класс для создания страницы
 */
class PageLayout
{
    private ?string $controller = null;

    public function __construct()
    {
    }

    /**
     * {}
     */
    public function template($pageProps = []): void
    {
        $action = $this->getPageForTemplate();
        $component = ucfirst($action);
        ?>
        <div id="root"></div>
        <script type="text/babel" src="/pages/<?= $action ?>.js"></script>
        <script type="text/babel">
            let options = <?=json_encode($pageProps)?>;
            ReactDOM.render(
              <<?=$component?> {...options} />,
              document.getElementById('root')
            );
        </script>
        <?php
    }

    /**
     * @return string
     */
    public function getPageForTemplate(): string
    {
        global $msc;
        $page = $msc->page;
        if ($msc->page == 'actions' && !$msc->table) {
            $page = 'actionsdb';
        }
        if ($msc->page == 'search' && $msc->table) {
            $page = 'searchTable';
        }
        if ($msc->page == 'tbl_list' && GET('action') == 'structure') {
            $page = 'tbl_struct_view';
        }
        if ($msc->page == 'tbl_struct' && GET('action') == 'add_key') {
            $page = 'tbl_key_add';
        }
        if ($msc->page == 'msc_configuration') {
            $page = 'config';
        }
        return $page;
    }

    /**
     * Отображение страницы
     */
    public function display()
    {
        global $msc;
        $currentPage = $msc->getCurrentPage();
        $currentHandler = $this->getHandler();
        $this->initController();

        if ($currentHandler == null && $this->controller == null) {
            $msc->page = 'db_list';
            $msc->addMessage('Страница не найдена');
            $currentHandler = $this->getHandler();
        }

        if ($msc->connected()) {
            $msc->dbViewStat();
        }

        if (isajax()) {
            $this->ajaxResult($currentHandler);
        }

        $contentMain = $this->getContentByHandler($currentHandler);

        include(MS_DIR_TPL . '_skin1.htm.php');
    }

    /**
     * @param $handler
     * @return string
     */
    public function getContentByHandler($handler): string
    {
        global $msc, $umaker; // нужны в подключаемом хендлере, не везде там прописаны глобалы
        $contentMain = null;
        ob_start();
        if ($this->controller) {
            new $this->controller();
        } else {
            include $handler;
        }
        $contentMain = ob_get_contents();
        ob_clean();
        return $contentMain;
    }

    /**
     * @param $currentHandler
     * @return void
     */
    public function ajaxResult($currentHandler): void
    {
        global $msc;
        if (array_key_exists('init', $_GET)) {
            $data = [
                'messages' => $msc->getMessagesData(),
                'databases' => Server::getDatabasesWithoutHidden(),
                'DB_HOST' => $msc->host,
                'DB_USERNAME' => $msc->user,
            ];
        } else {
            $pageProps = include $currentHandler;
            if (!is_array($pageProps)) {
                $pageProps = [];
            }
            $data = [
                'page' => $pageProps,
                'messages' => $msc->getMessagesData(),
            ];
        }
        ajaxResult($data);
    }

    /**
     * Определяем обработчик
     * @static
     */
    public function getHandler(): ?string
    {
        global $msc;
        $handlers = array(
            'exportSp' => 'export'
        );
        if (isset($handlers[$msc->page])) {
            $currentHandler = DIR_MYSQL . $handlers[$msc->page] . '.php';
        } else {
            $currentHandler = DIR_MYSQL . $msc->page . '.php';
        }
        if (file_exists($currentHandler)) {
            return $currentHandler;
        } else {
            return null;
        }
    }

    /**
     * Определяем обработчик
     * @static
     */
    private function initController(): void
    {
        global $msc;
        $className = ucfirst($msc->page);
        $path = 'controller/'.$className.'.php';
        if (file_exists($path)) {
            $this->controller = '\controller\\'.$className;
        }
    }

    /**
     * Общее меню менеджера
     * массивы в формате (s, action)
     */
    function getGlobalMenu()
    {
        global $msc, $umaker;
        $dbMenuGlobal = array(
            '[delim]1' => array('', ''),
            'поиск' => array('search', ''),
            'экспорт' => array('export', ''),
            'sql' => array('sql', ''),
            'операции' => array('actions', ''),
        );
        // если указан только раздел в строке запроса, выводим меню Сервер
        // чтобы при входе показать меню БД, а в db_list - меню Сервера
        $type = 'table';
        if (!$msc->connected()) {
            $dbMenu = [
                'ввести доступы к базе данных' => array('login', ''),
            ];
        } elseif ((GET('s') == 'db_list' || $msc->page == 'db_list' || $msc->page == 'users' || $msc->page == 'login') || substr(GET('s'), 0, 7) == 'server_') {
            $type = 'server';
            $dbMenu = array_merge(array(
                'базы данных' => array('db_list', ''),
                'логин' => array('login', ''),
                'статус' => array('server_status', ''),
                'переменные' => array('server_variables', ''),
                //'кодировки'   => array('server_collations', ''),
                'инфо' => array('server_users', ''),
            ), $dbMenuGlobal);

        } elseif (($msc->db != '' && $msc->table == '') || $msc->page == 'tbl_list') {
            $type = 'db';
            $dbMenu = array_merge(array(
                'таблицы' => array('tbl_list', ''),
                'создать таблицу' => array('tbl_add', ''),
            ), $dbMenuGlobal, array(
                '[delim]2' => array('', ''),
                'очистить' => array($msc->page, 'dbTruncate'),
                'удалить' => array($msc->page, 'dbDelete'),
                'удалить таблицы' => array($msc->page, 'dbTablesDelete')
            ));

        } else {
            $dbMenu = array_merge(array(
                //'таблицы'    => array('tbl_list', ''),
                'обзор' => array('tbl_data', ''),
                'структура' => array('tbl_struct', ''),
                'вставить' => array('tbl_change', ''),
                'создать таблицу' => array('tbl_add', '', $_SERVER['PHP_SELF'] . '?db=' . $msc->db),
            ), $dbMenuGlobal, array(
                '[delim]2' => array('', ''),
                'очистить' => array($msc->page, 'tableTruncate'),
                'удалить' => array($msc->page, 'tableDelete')
            ));
        }
        // создание базового урл
        // убрано использование ГЕТ, т.к. при переименовании БД во всех ссылках появлялась уже удалённая БД
        // которая была определена в ГЕТ запросе как db
        //$umaker->useGet = true;
        $url = $umaker->make();
        //$umaker->useGet = false;
        $menu = '<div class="globalMenu" id="globalMenu">' . "\r\n";
        foreach ($dbMenu as $title => $array) {
            if (stristr($title, '[delim]')) {
                $menu .= ' <b class="delim">|</b> ';
                continue;
            }
            list($page, $action) = $array;
            if (!empty($array[2])) {
                $curl = $array[2];
            } else {
                $curl = $url;
            }
            $extra = null;
            if (stristr($action, 'delete')) {
                $extra = ' class="delete" onClick="check(this, \'удаление\'); return false"';
            } elseif (stristr($action, 'truncate')) {
                $extra = ' class="truncate" onClick="check(this, \'очистка\'); return false"';
            }
            // создание урл
            $curl = UrlMaker::edit($curl, 's', $page);
            if ($action != '') {
                $curl = UrlMaker::edit($curl, 'action', $action);
            }
            if ($msc->page == $page && $action == GET('action')) {
                $menu .= '  <a href="' . $curl . '" class="cur"' . $extra . '>' . $title . '</a>' . "\r\n";
            } else {
                $menu .= '  <a href="' . $curl . '"' . $extra . '>' . $title . '</a>' . "\r\n";
            }
        }
        $menu .= '</div>' . "\r\n";
        return $menu;
    }

    /**
     * Меню в футере, дополнительное
     */
    function getFooterMenu()
    {
        global $msc;
        $base = str_replace('index.php', '', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
        $url = UrlMaker::edit($base, 'db', $msc->db);
        if ($msc->table != null && $msc->page != 'tbl_list') {
            $url = UrlMaker::edit($url, 'table', $msc->table);
        }
        $dbMenu = array(
            'Настройки' => array('msc_configuration', '')
        );
        $menu = '<div class="globalMenu">' . "\r\n";
        foreach ($dbMenu as $title => $array) {
            list($page, $action) = $array;
            $url = UrlMaker::edit($url, 's', $page);
            if ($action != '') {
                $url = UrlMaker::edit($url, 'action', $action);
            }
            if ($msc->page == $page && $action == GET('action')) {
                $menu .= '  <a href="' . $url . '" class="globalMenuCurrent">' . $title . '</a>' . "\r\n";
            } else {
                $menu .= '  <a href="' . $url . '">' . $title . '</a>' . "\r\n";
            }
        }
        $menu .= '</div>' . "\r\n";
        return $menu;
    }

    /**
     * Цепочка-меню
     */
    function getChainMenu(): string
    {
        global $msc;
        if (!$msc->connected()) {
            return 'нет подключения к БД';
        }
        $chain = '<a href="?s=db_list">DB</a>';
        if ($msc->db != null) {
            $chain .= ' &nbsp; <a href="?s=tbl_list&db=' . $msc->db . '&action=structure">&#8250;</a> &nbsp; <a href="?s=tbl_list&db=' . $msc->db . '">' . $msc->db . '</a>';
        }
        if ($msc->table != null) {
            $chain .= ' &nbsp; &#8250; &nbsp; <a href="?db=' . $msc->db . '&table=' . $msc->table . '&s=tbl_data">' . $msc->table . '</a>';
            if ($msc->page != 'tbl_data') {
                $chain .= ' &nbsp; &#8250; &nbsp; <a href="?s=' . $msc->page . '&db=' . $msc->db . '&table=' . $msc->table . '">' . $msc->page . '</a>';
            }
        }
        return $chain;
    }

    /**
     * Меню таблиц или селектор таблиц
     */
    function getTableMenu($selector = false, $auto = true)
    {
        global $msc;
        if ($msc->db == null) {
            return 'Не выбрана БД';
        }
        $menuTables = null;
        $selectorTables = null;

        $tables = DatabaseTable::getCashedTablesArray();
        if (count($tables) == 0) {
            return 'Нет таблиц в БД';
        }
        // статистика префиксов
        $prefixes = array();
        $rows = array();
        foreach ($tables as $row) {
            $t = $row->Name;
            $end = strlen($t) > 2 && strpos($t, '_', 3) > 0 ? strpos($t, '_', 3) : 50;
            $prefix = substr($t, 0, $end);
            $prefixes [$prefix] = !isset($prefixes [$prefix]) ? 1 : $prefixes [$prefix] + 1;
            if (substr($t, 0, strpos($t, '_')) == 'pr') {
                array_unshift($rows, $row);
            } else {
                $rows [] = $row;
            }
        }
        // создание меню и селектора
        $menuTables .= "\r\n" . '<div class="menuTables">' . "\r\n";
        $menuTables .= $this->addPopularTables();
        if ($auto === true) {
            $selectorTables .= '<select onchange="location=this.options[this.selectedIndex].value">' . "\r\n";
        } else {
            $selectorTables .= '<select name="' . $auto . '">' . "\r\n";
        }
        $greyEmpty = conf('greyempty');
        foreach ($rows as $t) {
            $end = strlen($t->Name) > 2 && strpos($t->Name, '_', 3) > 0 ? strpos($t->Name, '_', 3) : 50;
            $p = substr($t->Name, 0, $end);
            if (array_key_exists($p, $prefixes) && $prefixes[$p] > 1) {
                $style = 't1';
            } else {
                $style = 't2';
            }
            if ($greyEmpty && $t->Rows == 0) {
                $style .= '" style="color:#ccc';
            }
            if ($msc->table == $t->Name) {
                $menuTables .= $this->makeTableMenuItem($t->Name, 'cur');
                $selectorTables .= '  <option value="" selected><b>' . $t->Name . '</b></option>' . "\r\n";
            } else {
                $menuTables .= $this->makeTableMenuItem($t->Name, $style);
                $selectorTables .= '  <option value="' . $this->getMenuTableLink($t->Name) . '">' . $t->Name . '</option>' . "\r\n";
            }
        }
        $menuTables .= '</div>' . "\r\n";
        $selectorTables .= '</select>' . "\r\n";
        if ($selector) {
            return $selectorTables;
        } else {
            return $menuTables;
        }
    }

    function makeTableMenuItem($table, $class, $title = '')
    {
        return '  <a class="' . $class . '" title="' . $title . '" href="' . $this->getMenuTableLink($table) . '">' . $table . '</a>' . "\r\n";
    }

    function getMenuTableLink($table)
    {
        global $msc;
        // если есть текущая страница, то переход на неё (переход по структурам всех таблиц)
        if (!is_null($msc->page) && $msc->page == 'tbl_struct') {
            $link = '?db=' . $msc->db . '&table=' . $table . '&s=' . $msc->page;
        } else {
            $link = '?db=' . $msc->db . '&table=' . $table . '&s=tbl_data';
        }
        return $link;
    }

    /**
     * @return string
     */
    private function addPopularTables(): string
    {
        global $msc;
        $tables = $msc->getPopularTablesDb();
        if (count($tables) == 0) {
            return '';
        }
        ksort($tables);
        $menu = '';
        foreach ($tables as $table => $info) {
            $class = 't2';
            if ($info['count'] == 1) {
                $class = 'freq0';
            } elseif ($info['count'] < 3) {
                $class = 'freq1';
            } elseif ($info['count'] < 5) {
                $class = 'freq2';
            }
            if (!array_key_exists('info', $info)) {
                $info ['time'] = '';
            }
            if ($info['time']) {
                $lastTime = time() - $info['time'];
                if ($lastTime < 86400) {
                    $class = 't2';
                }
            }
            $menu .= $this->makeTableMenuItem($table, $class, $info['count'] . ' ' . $info['time']);
        }
        $menu .= '<a href="#" style="position: absolute; right: 0; top: 0" onclick="location.href=location.href + \'&resetPopular=1\'; return false;">reset</a> <hr />';
        return $menu;
    }
}

