<?php

declare(strict_types=1);

namespace controller;

use database\Table;
use database\Server;
use service\UrlMaker;

/**
 * Операции с БД и таблицами
 */
class Actions extends Base
{
    public function defaultAction(): array
    {
        global $msc;
        if ($msc->table == '') {
            return $this->databaseActions();
        } else {
            return $this->tableActions();
        }
    }

    /**
     * Действия - БД
     */
    public function databaseActions()
    {
        global $msc;

        $msc->pageTitle = "Действия - БД";
        $DQuery = UrlMaker::make('db', $msc->db, 's', 'actions');

        return [
            'db' => $_GET['db'],
            'url' => $DQuery,
            'charsets' => Server::getCharsetArray(),
            'processes' => $msc->driver->getProcessList(),
        ];
    }

    /**
     * Действия - таблица
     */
    public function tableActions(): array
    {
        global $msc;
        $msc->pageTitle = "Действия - таблица $msc->table";

        $row = $msc->driver->getTableInfo($msc->table);
        if (!$row) {
            $msc->error('Таблица не найдена');
            return [];
        }
        return [
            'url' => MS_URL . "?s=$msc->page&db=$msc->db&table=$msc->table",
            'table' => $msc->table,
            'db' => $msc->db,
            'ai' => $row->Auto_increment ?: '',
            'comment' => $row->Comment ?: '',
            'charset' => $row->Charset ?: '',
            'charsets' => Server::getCharsetArray(),
            'dbs' => Server::getDatabases(),
            'fields' => Table::getFieldNames($msc->table),
        ];
    }
}
