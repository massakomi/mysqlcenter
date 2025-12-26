<?php

namespace controller;

/**
 *
 */
class DbList
{
    /**
     *
     */
    public function __construct()
    {
        global $msc, $pagel;
        // Получаем массив баз данных
        $dbs = \Server::getDatabases();

        $msc->pageTitle = 'Список баз данных сервера ' . $msc->host . ' (всего: ' . count($dbs) . ')';
        $table = new \Table('contentTable', null, null, null, null, 'structureTableId');

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
            if ($mscExists = in_array('mysqlcenter', $dbs)) {
                $hidden = \MSTable::getHiddensArray();
            }
        }

        list($vi, $vs) = \Server::getServerVersion();

        $pageProps = [
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
        if (isajax()) {
            return $pageProps;
        }

        $pagel->template($pageProps);
    }
}
