<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

classLoad('DatabaseManager');

/**
 * Главная страница -  таблица таблиц БД а также удаление / очистка таблиц
 */

if (!defined('DIR_MYSQL')) {
    exit('Hacking attempt');
}

$tables = DatabaseTable::getCashedTablesArray();

if (count($tables) == 0) {
    $msc->addMessage('В базе данных нет таблиц');
}

// Простая таблица
if (GET('action') == 'full') {
    $msc->pageTitle = 'Полные данные таблиц "'.$msc->db.'" ';
    echo $contentMain = MSC_printObjectTable($tables);
    return;

// Исследование структуры
} elseif (GET('action') == 'structure') {
    $msc->pageTitle = 'Структура таблиц базы данных "'.$msc->db.'" ';
    foreach ($tables as $key => $table) {
        $tables [$key]->fields = getFields($table->Name);
        $tables [$key]->data = $msc->getData('SELECT * FROM '.$table->Name.' LIMIT 3');
    }
    $pageProps = [
        'tables' => $tables
    ];
    if (isajax()) {
        return $pageProps;
    }
    return include(MS_DIR_TPL . 'tbl_struct_view.htm.php');

// Полная таблица
} else {
    $msc->pageTitle = 'Список таблиц базы данных "'.$msc->db.'" ';
    $action = POST('act');
    foreach ($tables as $key => $o) {
        if (isset($_GET['makeInnodb'])) {
            if ($o->Engine == 'InnoDB') continue;
            echo '<br>'.$o->Name.' '.$o->Rows;
            $msc->query('ALTER TABLE `'.$o->Name.'` ENGINE = InnoDB');
            echo mysqli_error();
        }
        if ($_GET['drop']) echo 'DROP TABLE `' . $o->Name . '`;<br />';

        if ($action == 'analyze' || $action == 'check' || $action == 'flush' || $action == 'repair'
            || $action == 'optimize') {
            $sql = strtoupper($action) . ' TABLE `' . $o->Name . '`';
            if ($msc->query($sql)) {
                $msc->addMessage('Запрос выполнен', $sql, MS_MSG_SUCCESS);
            } else {
                $msc->addMessage('Ошибка запроса', $sql, MS_MSG_FAULT);
            }
        }
    }

    $pageProps = [
        'showtableupdated' => conf('showtableupdated') == '1',
        'tables' => $tables,
        'dirImage' => MS_DIR_IMG,
        'db' => $msc->db
    ];
    if (isajax()) {
        return $pageProps;
    }

    include(MS_DIR_TPL . 'tbl_list.htm.php');
}

