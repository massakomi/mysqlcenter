<?php
declare(strict_types=1);

namespace controller;

use database\Server;

/**
 *
 */
class TblData extends Base
{
    public function defaultAction(): array
    {
        global $msc;

        $directSQL = POST('sql');
        // если это прямой запрос (из sql.php), то разрешаем не указывать таблицу
        if (isset($directSQL) && $msc->table == '') {
            if (preg_match('~^SELECT.*FROM\s+([`\w\d]+)(\s+|;|,)~iUs', $directSQL . ' ', $t)) {
                $msc->table = str_replace('`', '', $t[1]);
            } else {
                $text = 'SELECT-запрос сформирован неправильно и не удалось найти таблицу в запросе';
                $msc->error($text);
                return [];
            }
        } elseif ($msc->table == '') {
            $msc->error('Не указана таблица в запросе');
            return [];
        }

        // Получение полей таблицы
        $fields = \DatabaseTable::getFields($msc->table);
        // Если полей нет, значит и таблицы нет
        if (!$fields || count($fields) == 0) {
            $msc->error("Таблицы $msc->table не существует");
            return [];
        }

        // Собираем массив имён полей, и также массив имён только ключевых полей
        $pk = [];
        $fieldsNames = [];
        foreach ($fields as $k => $v) {
            $fieldsNames [] = $v->Field;
            if (strchr($v->Key, 'PRI')) {
                $pk [] = $v->Field;
            }
        }

        // Определяем параметры сортировки, старт и части
        $order = $this->mscGetOrder(default: $pk[0] ?? '');
        $start = intval(GET('go', POST('go')));
        $part  = intval(GET('part', POST('part') > 0 ? POST('part') : MS_DEFAULT_PART));


        // Составляем запрос, если не определён запрос из вне
        if (!isset($directSQL)) {
            // Собираем where условие если требуется, для выборки
            $whereCondition = null;
            $query = POST('query', GET('query'));
            if ($query != '' && $query != 'Поиск или where') {
                if (preg_match('~([=<>]| (like|in) )~i', $query)) { // isWhere?
                    $whereCondition = ' WHERE ' . $query;
                } else {
                    $where = implode('` LIKE "%' . $query . '%" OR `', $fieldsNames);
                    $whereCondition = ' WHERE `' . $where . '` LIKE "%' . $query . '%"';
                }
            } elseif (GET('where') != null) {
                $whereCondition = ' WHERE ' . urldecode(stripslashes(GET('where')));
            } elseif (POST('byField') != null) {
                if (POST('like') == 'like') {
                    $whereCondition  = " WHERE `" . POST('field') . "` LIKE '%" . POST('byField') . "%'";
                } else {
                    $whereCondition  = " WHERE `" . POST('field') . "`='" . POST('byField') . "'";
                }
            }

            // Получаем кол-во рядов в таблице
            $count = 0;
            $result = $msc->fetchPdo('SELECT COUNT(*) as c FROM ' . $msc->table . ' ' . $whereCondition);
            if ($result && $row = $result->fetchObject()) {
                $count = $row->c;
            }
            if (GET('part') == 'all') {
                $part = $count;
            }
            // Создаём запрос и выводим инфо о нём
            $sql = "SELECT * FROM $msc->table $whereCondition $order";
            if ($msc->driverName == 'pgsql') {
                $sql .= " LIMIT $part OFFSET $start";
            } else {
                $sql .= " LIMIT $start, $part";
            }

            // Сразу выход, если ничего не найдено
            if ($count == 0) {
                $msc->pageTitle = "Таблица: $msc->table (пустая)";
                $msc->error("В таблице $msc->table нет данных");
                return [];
            }

        // Прямой запрос
        } else {
            // выборка общего кол-ва записей (пока такой вариант, нужно улучшать)
            // Внимание - тут возможно несколько вложенных таблиц или запросов
            $result = $msc->fetchPdo('EXPLAIN ' . $directSQL);
            if (!$result) {
                $msc->error('Не прошёл запрос', 'EXPLAIN ' . $directSQL);
                return [];
            }
            $count = 0;
            $part  = 0;

            // Для директ sql сообщение выводим тут
            $msc->notice($directSQL);
            $sql = $directSQL;
        }

        // Запрос и если ничего не найдено тут - выходим
        if (!$result = $msc->fetchPdo($sql)) {
            $msc->error('Ничего не найдено в таблице по запросу');
            return [];
        }

        $data = [];
        while ($row = $result->fetchObject()) {
            $data [] = $row;
        }
        $j = count($data);
        if (!$count) {
            $count = $j;
        }
        if ($count != $j) {
            $add = '';
            if ($start > 0) {
                $add = ", начиная с $start,";
            }
            $msc->pageTitle = "Таблица: $msc->table ($j строк$add из $count)";
        } else {
            $msc->pageTitle = "Таблица: $msc->table ($count)";
        }

        $pageProps = [
            'dirImage' => MS_DIR_IMG,
            'headWrap' => MS_HEAD_WRAP,
            'textCut' => MS_TEXT_CUT,
            'linksRange' => (int)MS_LIST_LINKS_RANGE,
            'db' => $msc->db,
            'table' => $msc->table,
            'count' => $count,
            'go' => $start,
            'order' => POST('order'),
            'part' => $part,
            'url' => \UrlMaker::make('s', '#s#'),
            'showtablecompare' => config('showtablecompare'),
            'dbs' => Server::getDatabases(),
            'directSQL' => isset($directSQL),
            'fields' => $fields,
            'data' => $data,
        ];
        \PopularTables::save(db: $msc->db, table: $msc->table);
        return $pageProps;
    }

    /**
     * Возвращает порядок текущей сортировки
     */
    private function mscGetOrder($default = null): string
    {
        if (!config('sortDescDefault') && $default) {
            $default .= '-';
        }
        $order = (POST('order') != null ? POST('order') : $default);
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
}
