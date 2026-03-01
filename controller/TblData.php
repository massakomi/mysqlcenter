<?php

declare(strict_types=1);

namespace controller;

use database\Server;
use database\Table;
use service\PopularTables;
use service\UrlMaker;

class TblData extends Base
{
    /**
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function defaultAction(): array
    {
        global $msc;

        $directSQL = POST('sql');
        // если это прямой запрос (из sql.php), то разрешаем не указывать таблицу
        if (isset($directSQL)) {
            if (preg_match('~^SELECT.*FROM\s+([`\w\d]+)(\s+|;|,)~iUs', $directSQL.' ', $t)) {
                $msc->table = str_replace('`', '', $t[1]);
            } else {
                return [
                    'data' => $msc->getData($directSQL),
                    'onlyData' => true,
                ];
            }
        } elseif ($msc->table == '') {
            $msc->error('Не указана таблица в запросе');

            return [];
        }

        // Получение полей таблицы
        $fields = Table::getFields($msc->table);
        // Если полей нет, значит и таблицы нет
        if (!$fields) {
            $msc->error("Таблицы $msc->table не существует");

            return [];
        }

        // Собираем массив имён полей, и также массив имён только ключевых полей
        $pk = [];
        $fieldsNames = [];
        foreach ($fields as $v) {
            $fieldsNames[] = $v->Field;
            if (strchr($v->Key, 'PRI')) {
                $pk[] = $v->Field;
            }
        }

        // Определяем параметры сортировки, старт и части
        $order = $this->mscGetOrder($msc->table, default: $pk[0] ?? '');
        $start = intval(GET('go', POST('go')));
        $part  = intval(GET('part', POST('part') > 0 ? POST('part') : MS_DEFAULT_PART));


        // Составляем запрос, если не определён запрос из вне
        if (empty($directSQL)) {
            $whereCondition = $this->getWhere($fields);

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
            $sql = "SELECT * FROM $msc->table $whereCondition $order";
            if ($msc->driverName == 'pgsql') {
                $sql .= " LIMIT $part OFFSET $start";
            } else {
                $sql .= " LIMIT $start, $part";
            }

            // Сразу выход, если ничего не найдено
            if ($count == 0) {
                if ($whereCondition) {
                    $msc->pageTitle = "Таблица: $msc->table";
                    $msc->notice("Ничего не найдено в таблице $msc->table по условию $whereCondition");
                } else {
                    $msc->pageTitle = "Таблица: $msc->table (пустая)";
                    $msc->notice("В таблице $msc->table нет данных");
                }

                return [];
            }

            // Прямой запрос
        } else {
            // выборка общего кол-ва записей (пока такой вариант, нужно улучшать)
            // Внимание - тут возможно несколько вложенных таблиц или запросов
            if (str_contains($directSQL, 'select')) {
                $result = $msc->fetchPdo('EXPLAIN '.$directSQL);
                if (!$result) {
                    $msc->error('Не прошёл запрос', 'EXPLAIN '.$directSQL);

                    return [];
                }
            }
            $count = 0;
            $part  = 0;

            // Для директ sql сообщение выводим тут
            $msc->notice($directSQL);
            $sql = $directSQL;
        }

        $data = $msc->getData($sql);
        if (!$data) {
            $msc->error('Ничего не найдено в таблице по запросу');

            return [];
        }

        $countSelected = count($data);
        $this->setPageTitle($count, $countSelected, $start);

        PopularTables::save(db: $msc->db, table: $msc->table);

        return [
            'dirImage' => MS_DIR_IMG,
            'textCut' => MS_TEXT_CUT,
            'linksRange' => (int) MS_LIST_LINKS_RANGE,
            'db' => $msc->db,
            'table' => $msc->table,
            'count' => $count ?: $countSelected,
            'go' => $start,
            'order' => POST('order'),
            'part' => $part,
            'url' => UrlMaker::make('s', '#s#'),
            'showTableCompare' => config('showTableCompare'),
            'dbs' => Server::getDatabases(),
            'directSQL' => isset($directSQL),
            'fields' => $fields,
            'data' => $data,
        ];
    }

    private function getWhere(array $fields): ?string
    {
        global $msc;
        $whereCondition = null;
        $query = POST('query', GET('query'));
        if ($query != '' && $query != 'Поиск или where') {
            $query = trim($query);
            if (preg_match('~([=<>]| (like|in|is) )~i', $query)) { // isWhere?
                $whereCondition = $this->fixPgSqlQuotes(' WHERE '.$query);
            } else {
                $whereCondition = Table::whereCondition($fields, $query);
            }
        } elseif (GET('where') != null) {
            $whereCondition = $this->fixPgSqlQuotes(' WHERE '.urldecode(stripslashes(GET('where'))));
        } elseif (POST('byField') != null) {
            $field = POST('field');
            $byField = POST('byField');
            if ($msc->driverName == 'pgsql') {
                $field = '"'.$field.'"';
            } else {
                $field = '`'.$field.'`';
            }
            if (POST('like') == 'like') {
                $like = $msc->driverName == 'pgsql' ? 'ILIKE' : 'LIKE';
                $whereCondition  = " WHERE $field $like '%$byField%'";
            } else {
                $whereCondition  = " WHERE $field='$byField'";
            }
        }
        return $whereCondition;
    }

    private function fixPgSqlQuotes(string $whereCondition): string
    {
        global $msc;
        if ($msc->driverName == 'pgsql') {
            // Replace backticks with double quotes for PostgreSQL
            // and double quotes with single quotes for values
            $whereCondition = str_replace('"', "'", $whereCondition);
            $whereCondition = str_replace('`', '"', $whereCondition);
        }

        return $whereCondition;
    }

    private function setPageTitle(int $count, int $countSelected, int $start): void
    {
        global $msc;
        if (!$count) {
            $count = $countSelected;
        }
        if ($count != $countSelected) {
            $add = '';
            if ($start > 0) {
                $add = ", начиная с $start,";
            }
            $msc->pageTitle = "Таблица: $msc->table ($countSelected строк$add из $count)";
        } else {
            $msc->pageTitle = "Таблица: $msc->table ($count)";
        }
    }

    /**
     * Возвращает порядок текущей сортировки.
     */
    private function mscGetOrder(string $table, ?string $default = null): string
    {
        if (!config('sortDescDefault') && $default) {
            $default .= '-';
        }
        $order = POST('order');
        if ($order === null) {
            $cookieKey = 'table_sort_' . $table;
            $order = $_COOKIE[$cookieKey] ?? $default;
        }
        if ($order != null) {
            if (!strchr($order, '-')) {
                $order .= ' DESC';
            } else {
                $order = str_replace('-', '', $order).' ASC';
            }
            $order = "ORDER BY $order";
        } else {
            $order = 'ORDER BY 1';
        }

        return $order;
    }
}
