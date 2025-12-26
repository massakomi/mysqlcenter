<?php

/**
 *
 */
class Menu
{

    /**
     * Цепочка-меню
     */
    public function getChainMenu(): string
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
     * Меню в футере, дополнительное
     */
    public function getFooterMenu()
    {
        global $msc;
        $base = str_replace('index.php', '', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
        $url = UrlMaker::edit($base, 'db', $msc->db);
        if ($msc->table != null && $msc->page != 'tbl_list') {
            $url = UrlMaker::edit($url, 'table', $msc->table);
        }
        $dbMenu = [
            'Настройки' => ['config', '']
        ];
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
     * Общее меню менеджера
     * массивы в формате (s, action)
     */
    public function getGlobalMenu()
    {
        global $msc, $umaker;
        $dbMenu = $this->globalMenuItems();
        $url = $umaker->make();
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
     *
     */
    private function globalMenuItems()
    {
        global $msc;
        $dbMenuGlobal = [
            '[delim]1' => ['', ''],
            'поиск' => ['search', ''],
            'экспорт' => ['export', ''],
            'sql' => ['sql', ''],
            'операции' => ['actions', ''],
        ];
        // если указан только раздел в строке запроса, выводим меню Сервер
        // чтобы при входе показать меню БД, а в db_list - меню Сервера
        if (!$msc->connected()) {
            $dbMenu = [
                'ввести доступы к базе данных' => ['login', ''],
            ];
        } elseif ((GET('s') == 'db_list' || $msc->page == 'db_list' || $msc->page == 'users' || $msc->page == 'login') || substr(GET('s'), 0, 7) == 'server_') {
            $dbMenu = array_merge([
                'базы данных' => ['db_list', ''],
                'логин' => ['login', ''],
            ], $dbMenuGlobal);

        } elseif (($msc->db != '' && $msc->table == '') || $msc->page == 'tbl_list') {
            $dbMenu = array_merge([
                'таблицы' => ['tbl_list', ''],
                'создать таблицу' => ['tbl_add', ''],
            ], $dbMenuGlobal, [
                '[delim]2' => ['', ''],
                'очистить' => [$msc->page, 'dbTruncate'],
                'удалить' => [$msc->page, 'dbDelete'],
                'удалить таблицы' => [$msc->page, 'dbTablesDelete']
            ]);

        } else {
            $dbMenu = array_merge([
                'обзор' => ['tbl_data', ''],
                'структура' => ['tbl_struct', ''],
                'вставить' => ['tbl_change', ''],
                'создать таблицу' => ['tbl_add', '', $_SERVER['PHP_SELF'] . '?db=' . $msc->db],
            ], $dbMenuGlobal, [
                '[delim]2' => ['', ''],
                'очистить' => [$msc->page, 'tableTruncate'],
                'удалить' => [$msc->page, 'tableDelete']
            ]);
        }
        return $dbMenu;
    }

    /**
     * Меню таблиц или селектор таблиц
     */
    public function getTableMenu($selector = false, $auto = true)
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
                $class = 't1';
            } else {
                $class = 't2';
            }
            if ($greyEmpty && $t->Rows == 0) {
                $class .= ' empty';
            }
            $menuTables .= $this->makeTableMenuItem($t->Name, $class);
            if ($msc->table == $t->Name) {
                $selectorTables .= '  <option value="" selected><b>' . $t->Name . '</b></option>' . "\r\n";
            } else {
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

    private function makeTableMenuItem($table, $class, $title = '')
    {
        global $msc;
        if ($msc->table == $table){
            $class .= ' cur';
        }
        return '  <a class="' . $class . '" title="' . $title . '" href="' . $this->getMenuTableLink($table) . '">' . $table . '</a>' . "\r\n";
    }

    private function getMenuTableLink($table)
    {
        global $msc;
        // если есть текущая страница, то переход на неё (переход по структурам всех таблиц)
        if ($msc->page == 'tbl_struct') {
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