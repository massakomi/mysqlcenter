<?php
declare(strict_types=1);

namespace controller;

use database\MSTable;
use database\Server;

/**
 *
 */
class DbList extends Base
{
    /**
     * @return array|void
     */
    public function defaultAction(): array
    {
        global $msc;
        // Получаем массив баз данных
        $dbs = Server::getDatabases();

        $msc->pageTitle = 'Список баз данных сервера ' . $msc->host . ' (всего: ' . count($dbs) . ')';
        //$table = new \Table('contentTable', null, null, null, null, 'structureTableId');

        // Определяем, показывать ли полную информацию или нет
        $showFullInfo = GET('mode') == 'full';

        // Отображаем список баз данных с полной информацией
        if ($showFullInfo) {
            foreach ($dbs as $j => $db) {
                $dbItem = [
                    'name' => $db,
                    'extra' => []
                ];
                $result = $msc->getData('SHOW TABLE STATUS FROM `' . $db . '`', \PDO::FETCH_OBJ);
                foreach ($result as $row) {
                    $dbItem ['extra'][] = $row;
                }
                $dbs [$j] = $dbItem;
            }

        // Отображаем список баз данных с краткой информацией
        } else {
            $hidden = [];
            if (in_array('mysqlcenter', $dbs)) {
                $hidden = MSTable::getHiddensArray();
            }
        }

        list(, $vs) = Server::getServerVersion();

        return [
            'databases' => $dbs,
            'hiddens' => $hidden,
            'dbHost' => $msc->host,
            'showFullInfo' => $showFullInfo,
            'folder' => MS_DIR_IMG,
            'url' => MS_URL,
            'dbname' => $msc->db,
            'phpversion' => phpversion(),
            'mysqlVersion' => $vs,
        ];
    }
}
