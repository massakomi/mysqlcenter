<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

/**
 * Класс для создания страницы
 */
class PageLayout
{

    /**
     * Констркутор
     */
    function __construct()
    {
        global $msc;
    }

    /**
     * Отображение страницы
     */
    public function display()
    {
        global $msc, $umaker, $connection;
        $currentHandler = $this->_getHandler();
        $currentPage = $msc->getCurrentPage();
        if ($currentHandler == null) {
            $msc->page = 'db_list';
            $msc->addMessage('Страница не найдена');
            $currentHandler = $this->_getHandler();
        }

        global $debugger;
        if (isset($debugger)) $debugger->et('До контента');

        // Статистика просмотров
        $dbs = Server::getDatabases();
        if (in_array('mysqlcenter', $dbs)) {
            include_once 'includes/MSTable.php';

            // Статистика просмотров баз данных
            if ($msc->db != '') {
                $result = $msc->query($t = 'SELECT * FROM mysqlcenter.db_info WHERE db_name="' . $msc->db . '"');
                $a = array();
                while ($o = mysqli_fetch_object($result)) {
                    $a [] = $o;
                }
                if (count($a) == 0) {
                    $msc->query('REPLACE INTO mysqlcenter.db_info VALUES("' . $msc->db . '", 1, 1, "' . date('Y-m-d H:i:s') . '")', null, 0);
                } else {
                    $msc->query('UPDATE mysqlcenter.db_info SET views=views+1, last_view="' . date('Y-m-d H:i:s') .
                        '" WHERE db_name="' . $msc->db . '"', null, 0);
                }

                // Статистика просмотров таблиц
                if ($msc->table != '') {
                    $result = $msc->query($t = 'SELECT * FROM mysqlcenter.table_info
                        WHERE db_name="' . $msc->db . '" AND table_name="' . $msc->table . '"');
                    $a = array();
                    while ($o = mysqli_fetch_object($result)) {
                        $a [] = $o;
                    }
                    if (count($a) == 0) {
                        $msc->query('REPLACE INTO mysqlcenter.table_info VALUES("' . $msc->db . '", "' . $msc->table . '", 1, 1, "' . date('Y-m-d H:i:s') . '")', null, 0);
                    } else {
                        $msc->query('UPDATE mysqlcenter.table_info SET views=views+1, last_view="' . date('Y-m-d H:i:s') .
                            '" WHERE db_name="' . $msc->db . '" AND table_name="' . $msc->table . '"', null, 0);
                    }
                }
            }
        }

//        $generate_time = round(round(array_sum(explode(" ", microtime())), 10) - $msc->timer, 5);
//        $memory_get_peak_usage = formatSize(memory_get_peak_usage());
//        $memory_get_usage = formatSize(memory_get_usage());
//        $includeSize = formatSize(array_sum(array_map(fn($file) => filesize($file), get_included_files())));
//        $memory_limit = ini_get('memory_limit');


        if (isajax()) {

            if (!$_GET['init']) {
                $pageProps = include $currentHandler;
            }
            if (!$pageProps) {
                $pageProps = [];
            }

            $data = [
                'page' => $pageProps,
                'main' => [
                    'handler' => $currentHandler,
                    'page' => $msc->page,
                    'db' => $msc->db,
                    'table' => $msc->table,
                ],

                'getWindowTitle' => $msc->getWindowTitle(),
                'getPageTitle' => $msc->getPageTitle(),
                'messages' => $msc->getMessagesData(),
                'queries' => $msc->queries,

                'generate_time' => round(round(array_sum(explode(" ", microtime())), 10) - $msc->timer, 5),
                'memory_get_peak_usage' => formatSize(memory_get_peak_usage()),
                'memory_get_usage' => formatSize(memory_get_usage()),
                'includeSize' => formatSize(array_sum(array_map(fn($file) => filesize($file), get_included_files()))),
                'memory_limit' => ini_get('memory_limit'),

                'DB_HOST' => DB_HOST,
                'DB_USERNAME_CUR' => DB_USERNAME_CUR,

                //'enterType' => $this->enterType,
                //'cookies' => $_COOKIE,
                //'session_id' => session_id()

            ];

            /*if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                echo '<pre>'; print_r($_SERVER); echo '</pre>'; exit;
            }*/

            exit(json_encode($data));
        }

        // Получаем контент
        $contentMain = null;
        ob_start();
        include $currentHandler;
        $contentMain = ob_get_contents();
        ob_clean();
        // Буферизация
        // выключил, т.к. на одном сервере возникла ошибка, что этот механизм запрещен
        //$hae = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
        //if (extension_loaded('zlib') && (strpos($hae, 'gzip') !== false || strpos($hae, 'deflate') !== false)) {
        //	@ob_start('ob_gzhandler');
        //}
        global $debugger;
        if (isset($debugger)) $debugger->et('До скина');
        // Скин
        include(MS_DIR_TPL . '_skin1.htm.php');
    }

    /**
     * @param string $errorMessage
     */
    function loginPage($errorMessage = '')
    {
        if (isajax()) {
            $data = [
                'message' => $errorMessage,
                'getWindowTitle' => 'Login to MysqlCenter',
                'page' => 'login',
                'post' => $_POST,
            ];
            exit(json_encode($data));
        }
        include(MS_DIR_TPL . 'login.htm');
    }

    /**
     * Определяем обработчик
     * @static
     */
    function _getHandler()
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
        if ((GET('s') == 'db_list' || $msc->page == 'db_list' || $msc->page == 'users') || substr(GET('s'), 0, 7) == 'server_') {
            $type = 'server';
            $dbMenu = array_merge(array(
                'базы данных' => array('db_list', ''),
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
            '<img src="' . MS_DIR_IMG . 'help.gif" alt="" border="0" title="MySQL справка" align="absbottom" />' => array('msc_help', ''),
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
    function getChainMenu()
    {
        global $msc;
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
        $msc->selectDb($msc->db);
        global $debugger;
        if (isset($debugger)) $debugger->et('До getCashedTablesArray');
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
            // если есть текущая страница, то переход на неё (переход по структурам всех таблиц)
            if (!is_null($msc->page) && $msc->page == 'tbl_struct') {
                $link = '?db=' . $msc->db . '&table=' . $t->Name . '&s=' . $msc->page;
            } else {
                $link = '?db=' . $msc->db . '&table=' . $t->Name . '&s=tbl_data';
            }
            if ($msc->table == $t->Name) {
                $menuTables .= '  <a class="cur" href="' . $link . '">' . $t->Name . '</a>' . "\r\n";
                $selectorTables .= '  <option value="" selected><b>' . $t->Name . '</b></option>' . "\r\n";
            } else {
                $menuTables .= '  <a class="' . $style . '" href="' . $link . '">' . $t->Name . '</a>' . "\r\n";
                $selectorTables .= '  <option value="' . $link . '">' . $t->Name . '</option>' . "\r\n";
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

    /**
     * Селектор баз данных
     * @param string  Имя селектора
     * @param boolean Создать автопереходчик по БД
     */
    function getDBSelector($name = 'db', $auto = true, $exclude = null)
    {
        global $msc;
        if ($auto) {
            $selectorDB = '<select name="' . $name . '" onChange="d = this.options[this.selectedIndex].text; cook.set(\'mc_db\', d, 31, \'/\'); location=\'?db=\'+d">' . "\r\n";
            $selectorDB .= "  <option>Выберите базу данных</option>\r\n";
        } else {
            $selectorDB = '<select name="' . $name . '">' . "\r\n";
        }
        $dbs = Server::getDatabases();
        foreach ($dbs as $db) {
            if ($exclude == $db) {
                continue;
            }
            if ($msc->db == $db) {
                $selectorDB .= "  <option selected>$db</option>\r\n";
            } else {
                $selectorDB .= "  <option>$db</option>\r\n";
            }
        }
        $selectorDB .= '</select>' . "\r\n";
        return $selectorDB;
    }
}

?>
