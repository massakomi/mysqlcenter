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
     * @return array
     */
    public function defaultAction(): array
    {
        global $msc;

        // Получаем массив баз данных
        $showStatInfo = GET('type') == 'stat';
        $showFullInfo = GET('type') == 'full';
        if ($showFullInfo) {
            $dbs = $msc->driver->getDatabases();
        } else {
            $dbs = Server::getDatabases();

            // Отображаем список баз данных с полной информацией
            if ($showStatInfo) {
                foreach ($dbs as $j => $db) {
                    $dbItem = [
                        'name' => $db,
                        'extra' => []
                    ];
                    $result = $msc->driver->getTables($db);
                    foreach ($result as $row) {
                        $dbItem ['extra'][] = $row;
                    }
                    $dbs [$j] = $dbItem;
                }
            }
        }

        $msc->pageTitle = 'Список баз данных сервера ' . $msc->host . ' (всего: ' . count($dbs) . ')';

        $hidden = [];
        if (in_array('mysqlcenter', $dbs)) {
            $hidden = MSTable::getHiddensArray();
        }

        return [
            'databases' => $dbs,
            'hiddens' => $hidden,
            'dbHost' => $msc->host,
            'showFullInfo' => $showFullInfo,
            'showStatInfo' => $showStatInfo,
            'folder' => MS_DIR_IMG,
            'url' => MS_URL,
            'dbname' => $msc->db,
            'phpversion' => phpversion(),
            'serverVersion' => Server::getServerVersion(),
        ];
    }
}
