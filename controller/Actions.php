<?php

declare(strict_types=1);

namespace controller;

use database\Table;
use database\Server;
use service\UrlMaker;

/**
 * Операции с БД и таблицами.
 */
class Actions extends Base
{
    /**
     * @return array<string, mixed>
     */
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
     * Действия - БД.
     *
     * @return array<string, mixed>
     */
    public function databaseActions(): array
    {
        global $msc;

        $msc->pageTitle = 'Действия - БД';
        $DQuery = UrlMaker::make('db', $msc->db, 's', 'actions');

        return [
            'db' => $msc->db,
            'url' => $DQuery,
            'charsets' => Server::getCharsetArray(),
        ];
    }

    /**
     * Действия - таблица.
     *
     * @return array<string, mixed>
     */
    public function tableActions(): array
    {
        global $msc, $pdo;
        $msc->pageTitle = "Действия - таблица $msc->table";
        $row = $msc->driver->getTableInfo($msc->table);
        if (!$row) {
            $msc->error('Таблица не найдена');

            return [];
        }
        $identityInfo = $msc->driver->getIdentityInfo($msc->table);

        return [
            'url' => MS_URL."?s=$msc->page&db=$msc->db&table=$msc->table",
            'table' => $msc->table,
            'db' => $msc->db,
            'ai' => $row->Auto_increment ?: '',
            'comment' => $row->Comment ?: '',
            'charset' => $row->Charset ?: '',
            'charsets' => Server::getCharsetArray(),
            'dbs' => Server::getDatabases(),
            'fields' => Table::getFieldNames($msc->table),
            'identityInfo' => $identityInfo,
        ];
    }
}
