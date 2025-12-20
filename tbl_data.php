<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Табличные строки
 */


/**
 * Возвращает порядок текущей сортировки
 */
function mscGetOrder($default=null) {
    $order = (GET('order') != null ? GET('order') : $default);
    if ($order != null) {
        if (!strchr($order, '-')) {
            $order .= ' DESC';
        } else {
            $order = str_replace('-', '', $order) . ' ASC';
        }
        $order = "ORDER BY $order";
    } else {
        $order = 'ORDER BY 1';
    }
    return $order;
}


if (!defined('DIR_MYSQL')) {
    exit('Hacking attempt');
}

if (isset($_GET['fullText'])) {
    $_GET['fullText'] = conf('tblfullstart') == '1' ? '1' : $_GET['fullText'];
}

// если это прямой запрос (из sql.php), то разрешаем не указывать таблицу
if (isset($directSQL) && $msc->table == '') {
    //echo '"'.$directSQL.'"';
    if (preg_match('~^SELECT.*FROM\s+([`\w\d]+)(\s+|;|,)~iUs', $directSQL.' ', $t)) {
        $msc->table = str_replace('`', '', $t[1]);
    } else {
        return $msc->addMessage('SELECT-запрос сформирован неправильно и не удалось найти таблицу в запросе', null, MS_MSG_FAULT);
    }
} elseif ($msc->table == '') {
    return $msc->addMessage('Не указана таблица в запросе', null, MS_MSG_FAULT);
}

// Получение полей таблицы
$fields = getFields($msc->table);
// Если полей нет, значит и таблицы нет
if (!$fields || count($fields) == 0) {
    return $msc->addMessage("Таблицы $msc->table не существует", null, MS_MSG_FAULT, $msc->error);
}

// Собираем массив имён полей, и также массив имён только ключевых полей
$pk = array();
$fieldsNames = array();
foreach ($fields as $k => $v) {
    $fieldsNames []= $v->Field;
    if (strchr($v->Key, 'PRI')) {
        $pk []= $v->Field;
    }
}

// Определяем параметры сортировки, старт и части
$order = mscGetOrder(isset($pk[0]) ? $pk[0] : '');
$start = intval(GET('go'));
$part  = intval(GET('part', MS_DEFAULT_PART));

// Составляем запрос, если не определён запрос из вне
if (!isset($directSQL)) {

    // Собираем where условие если требуется, для выборки
    $whereCondition = null;
    if (POST('query') != '') {
        $whereCondition = ' WHERE `' . implode('` LIKE "%'.POST('query').'%" OR `', $fieldsNames) . '` LIKE "%'.POST('query').'%"';
    } elseif (GET('where') != null) {
        $whereCondition = ' WHERE ' . urldecode(stripslashes(GET('where')));
    } elseif (POST('byField') != null) {
        if (POST('like') == 'like') {
            $whereCondition  = " WHERE `".POST('field')."` LIKE '%".POST('byField')."%'";
        } else {
            $whereCondition  = " WHERE `".POST('field')."`='".POST('byField')."'";
        }
    }

    // Получаем кол-во рядов в таблице
    $count = 0;
    $result = $msc->fetchPdo('SELECT COUNT(*) as c FROM '.$msc->table.' '.$whereCondition);
    if ($result && $row = $result->fetchObject()) {
        $count = $row->c;
    }
    if (GET('part') == 'all') {
        $part = $count;
    }
    // Создаём запрос и выводим инфо о нём
    $sql = "SELECT * FROM $msc->table $whereCondition $order LIMIT $start, $part";


    // Сразу выход, если ничего не найдено
    if ($count == 0) {
        $msc->pageTitle = "Таблица: $msc->table (пустая)";
        $msc->addMessage("В таблице $msc->table нет данных", $sql, MS_MSG_SIMPLE);
        return null;
    } else {
        //$msc->addMessage('Выбрано', $sql);
    }

// Прямой запрос
} else {

    // выборка общего кол-ва записей (пока такой вариант, нужно улучшать)
    // Внимание - тут возможно несколько вложенных таблиц или запросов
    $result = $msc->fetchPdo('EXPLAIN ' . $directSQL);
    if (!$result) {
        $msc->notice('Не прошёл запрос', 'EXPLAIN ' . $directSQL);
        return;
    }
    $a = $result->fetchObject();
    $count = $a->rows;

    // Часть пока будет равна всем данным, потому что лимита нет. И ссылок не будет.
    $part  = $count;

    // Для директ sql сообщение выводим тут
    $msc->addMessage('Выбрано', $directSQL);

    $sql = $directSQL;

}

// Запрос и если ничего не найдено тут - выходим
if (!$result = $msc->fetchPdo($sql)) {
    $msc->addMessage('Ничего не найдено в таблице по запросу', $sql, MS_MSG_SIMPLE);
    return null;
}



// Создаём таблицу из результата $result
$headers = array('<a href="'.$umaker->switcher('fullText', '1').'" title="Показать полные значения всех полей '.
    'и убрать переносы заголовков полей" class="hiddenSmallLink" style="color:white">full</a>', '&nbsp;', '&nbsp;');
$table = new Table('contentTable');
$table->setInterlaceClass('', 'interlace');
$data = [];
while ($row = $result->fetchObject()) {
    $data []= $row;
}
$j = count($data);
if ($count != $j) {
    $msc->pageTitle = "Таблица: $msc->table ($j строк из $count всего)";
} else {
    $msc->pageTitle = "Таблица: $msc->table ($count строк)";
}

$pageProps = [
    'dirImage' => MS_DIR_IMG,
    'headWrap' => MS_HEAD_WRAP,
    'textCut' => MS_TEXT_CUT,
    'linksRange' => (int)MS_LIST_LINKS_RANGE,
    'db' => $msc->db,
    'table' => $msc->table,
    'count' => $count,
    'part' => $part,
    'url' => $umaker->make('s', '#s#'),
    'showtablecompare' => conf('showtablecompare'),
    'dbs' => Server::getDatabases(),
    'directSQL' => isset($directSQL),
    'fields' => $fields,
    'data' => $data,
];
if (isajax()) {
    return $pageProps;
}
$msc->addPopularTable($msc->table);

$this->template($pageProps);
