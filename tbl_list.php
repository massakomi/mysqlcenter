<?php

if (!defined('DIR_MYSQL')) {
    exit('Hacking attempt');
}

global $msc;

$tables = DatabaseTable::getCashedTablesArray();
if (count($tables) == 0) {
    $msc->addMessage('В базе данных нет таблиц');
}


// Исследование структуры
if (GET('action') == 'structure' || GET('mode') == 'structure') {
    $msc->pageTitle = 'Структура таблиц базы данных "' . $msc->db . '" ';
    foreach ($tables as $key => $table) {
        $tables [$key]->fields = DatabaseTable::getFields($table->Name);
        $tables [$key]->data = $msc->getData('SELECT * FROM ' . $table->Name . ' LIMIT 3');
    }
    $pageProps = [
        'tables' => $tables
    ];
    if (isajax()) {
        return $pageProps;
    }
    $this->template($pageProps);

// Полная таблица
} else {
    $msc->pageTitle = 'Список таблиц базы данных "' . $msc->db . '" ';
    $action = POST('act');
    foreach ($tables as $key => $o) {
        if (array_key_exists('drop', $_GET)) {
            echo 'DROP TABLE `' . $o->Name . '`;<br />';
        }

        if (
            $action == 'analyze' || $action == 'check' || $action == 'flush' || $action == 'repair'
            || $action == 'optimize'
        ) {
            $sql = strtoupper($action) . ' TABLE `' . $o->Name . '`';
            if ($msc->execPdo($sql)) {
                $msc->addMessage('Запрос выполнен', $sql, MS_MSG_SUCCESS);
            } else {
                $msc->addMessage('Ошибка запроса', $sql, MS_MSG_FAULT);
            }
        }
    }

    $pageProps = [
        'showtableupdated' => conf('showtableupdated') == '1',
        'full' => GET('action') == 'full',
        'tables' => $tables,
        'dirImage' => MS_DIR_IMG,
        'db' => $msc->db
    ];
    if (isajax()) {
        return $pageProps;
    }

    $this->template($pageProps);
}
