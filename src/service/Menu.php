<?php

declare(strict_types=1);

namespace service;

use database\Table;

/**
 * Класс генерирующий разные html блоки
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
            $chain .= ' &nbsp; &#8250; &nbsp; ';
            $chain .= '<a href="?s=tbl_list&db=' . $msc->db . '">' . $msc->db . '</a>';
        }
        if ($msc->table != null) {
            $chain .= ' &nbsp; &#8250; &nbsp; ';
            $chain .= "<a href='?s=tbl_data&db=$msc->db&table=$msc->table'>$msc->page</a>";
            if ($msc->page != 'tbl_data') {
                $chain .= ' &nbsp; &#8250; &nbsp; ';
                $chain .= "<a href='?s=$msc->page&db=$msc->db&table=$msc->table'>$msc->page</a>";
            }
        }
        return $chain;
    }

    /**
     * Общее меню менеджера
     * массивы в формате (s, action)
     */
    public function getGlobalMenu()
    {
        global $msc;
        $dbMenu = $this->globalMenuItems();
        $url = UrlMaker::make();
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
            $curl = UrlMaker::edit(url: $curl, name: 's', value: $page);
            if ($action != '') {
                $curl = UrlMaker::edit(url: $curl, name: 'action', value: $action);
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
    private function globalMenuItems(): array
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
        } elseif (in_array($msc->page, ['db_list', 'users', 'info', 'login', 'config'])) {
            $dbMenu = array_merge([
                'базы данных' => ['db_list', ''],
                'пользователи' => ['users', ''],
                'информация' => ['info', ''],
            ], $dbMenuGlobal);
        } elseif ($msc->db != '' && $msc->table == '') {
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
    public function getTableMenu($selector = false, $auto = true): string
    {
        global $msc;
        if ($msc->db == null) {
            return 'Не выбрана БД';
        }
        $menuTables = null;
        $selectorTables = null;

        $tables = Table::getCashedTablesArray();
        if (count($tables) == 0) {
            return 'Нет таблиц в БД';
        }
        // статистика префиксов
        $prefixes = [];
        foreach ($tables as $row) {
            $t = $row->Name;
            $end = strlen($t) > 2 && strpos($t, '_', 3) > 0 ? strpos($t, '_', 3) : 50;
            $prefix = substr($t, 0, $end);
            $prefixes [$prefix] = !isset($prefixes [$prefix]) ? 1 : $prefixes [$prefix] + 1;
        }
        // создание меню и селектора
        $menuTables .= "\r\n" . '<div class="menuTables">' . "\r\n";
        $menuTables .= $this->addPopularTables();
        if ($auto === true) {
            $selectorTables .= '<select onchange="location=this.options[this.selectedIndex].value">' . "\r\n";
        } else {
            $selectorTables .= '<select name="' . $auto . '">' . "\r\n";
        }
        foreach ($tables as $t) {
            if (strlen($t->Name) > 2 && strpos($t->Name, '_', 3) > 0) {
                $end = strpos($t->Name, '_', 3);
            } else {
                $end = 50;
            }
            $p = substr($t->Name, 0, $end);
            if (array_key_exists($p, $prefixes) && $prefixes[$p] > 1) {
                $class = 't1';
            } else {
                $class = 't2';
            }
            if ($t->Rows == 0) {
                $class .= ' empty';
            }
            $menuTables .= $this->makeTableMenuItem(table: $t->Name, class: $class);
            if ($msc->table == $t->Name) {
                $selectorTables .= '  <option value="" selected><b>' . $t->Name . '</b></option>' . "\r\n";
            } else {
                $value = $this->getMenuTableLink(table: $t->Name);
                $selectorTables .= '  <option value="' . $value . '">' . $t->Name . '</option>' . "\r\n";
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
     * @param $table
     * @param $class
     * @param string $title
     * @return string
     */
    private function makeTableMenuItem($table, $class, string $title = ''): string
    {
        global $msc;
        if ($msc->table == $table) {
            $class .= ' cur';
        }
        $href = $this->getMenuTableLink($table);
        return '  <a class="' . $class . '" title="' . $title . '" href="' . $href . '">' . $table . '</a>' . "\r\n";
    }

    /**
     * @param $table
     * @return string
     */
    private function getMenuTableLink($table): string
    {
        global $msc;
        if (in_array($msc->page, ['tbl_struct', 'search', 'export', 'actions', 'tbl_change'])) {
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
        $tables = Table::getCashedTablesArray();
        if (count($tables) < 50) {
            return '';
        }
        $tables = PopularTables::forDb($msc->db);
        if (count($tables) == 0) {
            return '';
        }
        ksort($tables);
        $menu = '';
        $counts = array_column($tables, 'count');
        $avg = array_sum($counts) / count($counts);
        foreach ($tables as $table => $info) {
            if ($info['count'] > $avg) {
                $class = 't2';
            } elseif ($info['count'] < $avg / 2) {
                $class = 'freq0';
            } else {
                $class = 'freq1';
            }
            if (!array_key_exists('time', $info)) {
                $info ['time'] = '';
            }
            $lastTime = $lastTimeDays = '';
            if ($info['time']) {
                $lastTime = time() - $info['time'];
                $lastTimeDays = round($lastTime / 86400, 1);
                if ($lastTimeDays < 1) {
                    $class = 't2';
                }
                if ($lastTimeDays > 7) {
                    continue;
                }
            } else {
                continue;
            }
            $menu .= $this->makeTableMenuItem($table, $class, $info['count'] . ' ' . $lastTimeDays);
        }
        $url = UrlMaker::make('resetPopular', 1);
        $menu .= '<a href="' . $url . '" style="position: absolute; right: 0; top: 0">reset</a> <hr />';
        return $menu;
    }
}
