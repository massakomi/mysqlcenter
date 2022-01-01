<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

/**
 * Табличные строки
 */


/**
 * Создание ссылок на страницы
 *
 * @param integer
 * @param integer
 * @return string
 */
function getLinks($count, $part) {
  global $umaker;
  $links = null;
  // GET - чтобы если есть в запросе, то в любом случае вывести сылки
  if ($count > $part || GET('part') > 0) {
    $countPages  = ceil($count / $part);
    $currentPage = ceil(GET('go') / $part);
    $beginPage   = max(0, $currentPage - MS_LIST_LINKS_RANGE);
    $endPage     = min($countPages, $currentPage + MS_LIST_LINKS_RANGE);
    $links =  '<div class="contentPageLinks">'."\r\n";

    for ($i = $beginPage; $i < $endPage; $i ++) {
        $url = $umaker->make('go', $i * $part, 'part', GET('part'), 'sql', GET('sql'), 'order', GET('order'));
        if (GET('go') == $i * $part) {
            $links .= '  <a href="'.$url.'" class="cur">' . ($i + 1) . '</a>'."\r\n";
        } else {
            $links .= '  <a href="'.$url.'">' . ($i + 1) . '</a>'."\r\n";
        }
    }
    $a = array(30, 50, 100, 200, 300, 500, 1000, 'all');
    $links .= '  <select class="miniSelector" onchange="location=this.options[this.selectedIndex].value">'."\r\n";
    $links .= '    <option value="">...</option>'."\r\n";
    foreach ($a as $v) {
        $sel = '';
        if ($v == GET('part')) {
            $sel = ' selected="selected"';
        }
        $links .= '    <option value="'.$umaker->make('part', $v).'"'.$sel.'>'.$v.'</option>'."\r\n";
    }
    $links .= '  </select>'."\r\n";
    $links .= '</div>'."\r\n";
  }
  return $links;
}

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
  echo '"'.$directSQL.'"';
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
    return $msc->addMessage("Таблицы $msc->table не существует", null, MS_MSG_FAULT, mysqli_error());
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
    if (GET('query') != '') {
        $whereCondition = ' WHERE `' . implode('` LIKE "%'.GET('query').'%" OR `', $fieldsNames) . '` LIKE "%'.GET('query').'%"';
    } elseif (GET('where') != null) {
        $whereCondition = ' WHERE ' . urldecode(stripslashes(GET('where')));
    }

    // Получаем кол-во рядов в таблице
    $count = 0;
    $result = $msc->query('SELECT COUNT(*) as c FROM '.$msc->table.' '.$whereCondition);
    if ($result && $row = mysqli_fetch_object($result)) {
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
        $msc->addMessage('Выбрано', $sql);
    }

// Прямой запрос
} else {

    // выборка общего кол-ва записей (пока такой вариант, нужно улучшать)
    // Внимание - тут возможно несколько вложенных таблиц или запросов
    $a = $msc->query('EXPLAIN ' . $directSQL);
    if (!$a) {
        $msc->notice('Не прошёл запрос', 'EXPLAIN ' . $directSQL);
        return;
    }
    $a = mysqli_fetch_object($a);
    $count = $a->rows;

    // Часть пока будет равна всем данным, потому что лимита нет. И ссылок не будет.
    $part  = $count;

    // Для директ sql сообщение выводим тут
    $msc->addMessage('Выбрано', $directSQL);

    $sql = $directSQL;

}

// Запрос и если ничего не найдено тут - выходим
if (!$result = $msc->query($sql)) {
    $msc->addMessage('Ничего не найдено в таблице по запросу', $sql, MS_MSG_SIMPLE);
    return null;
}



// Создаём таблицу из результата $result
$headers = array('<a href="'.$umaker->switcher('fullText', '1').'" title="Показать полные значения всех полей '.
    'и убрать переносы заголовков полей" class="hiddenSmallLink" style="color:white">full</a>', '&nbsp;', '&nbsp;');
$table = new Table('contentTable');
$table->setInterlaceClass('', 'interlace');
$j = 0;
$data = [];
while ($row = mysqli_fetch_object($result)) {
    $data []= $row;
    if ($table->headerCont == null) {
        if (isset($directSQL)) {
            $fields = array();
            $a = null;
            foreach ($row as $k => $v) {
                $a->Field = $k;
                $a->Type  = is_numeric($k) ? 'int' : 'varchar';
                $fields []= $a;
                unset($a);
            }
        }
        $headers = array_merge($headers, getTableHeaders($fields, !isset($directSQL)));
        $table->makeRowHead($headers, ' valign="top"');
    }
    $values = array();
    // определение уникального ид ряда
    $idRow = null;
    $pkValues = array();
    if (count($pk) > 0) {
        foreach ($pk as $pkCurrent) {
            if (!isset($row->$pkCurrent)) {
                $msc->addMessage('Hey! Ключевого поля '.$pkCurrent.' не найдено в таблице!?');
                continue;
            }
            $pkValues []= $pkCurrent.'="'.$row->$pkCurrent.'"';
        }
    } else {
        foreach ($fieldsNames as $pkCurrent) {
            if ($row->$pkCurrent == null) {
                continue;
            }
            $pkValues []= $pkCurrent.'="'.$row->$pkCurrent.'"';
        }
    }
    $idRow = urlencode(implode(' AND ', $pkValues));
    // чекбокс
    $values []= '<input name="row[]" type="checkbox" value="'.$idRow.'" class="cb" id="c'.$idRow.'" onclick="checkboxer('.$j.', \'#row\')"; />';
    // создание ссылок на действия
    $p = MS_DIR_IMG;
    $u1 = $umaker->make('s','tbl_change','row',$idRow);
    $u2 = $umaker->make('s','tbl_data','row',$idRow);
    $onc = "msQuery('deleteRow', '$u2&id=row$j'); return false";
    $values []= '<a href="'.$u1.'" title="Редактировать ряд"><img src="'.$p.'edit.gif" alt="" border="0" /></a>';
    $values []= '<a href="#" onClick="'.$onc.'" title="Удалить ряд"><img src="'.$p.'close.png" alt="" border="0" /></a>';
    // загрузка данных
    $i = 0;
    foreach ($row as $k => $v) {
        $type = 'varchar';
        if (isset($fields[$i])) {
            $type = $fields[$i]->Type;
        }
        $val = processRowValue($v, $type);
        if ($k == 'query') {
            $val = '<a href="http://yandex.ru/yandsearch?text='.$v.'" target="_blank">'.$val.'</a>';
        }
        $values []= $val;
        $i++;
    }
    $table -> makeRow($values, ' id="row'.$j.'"');
    $j ++;
}

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

include(MS_DIR_TPL . 'tbl_data.htm.php');
