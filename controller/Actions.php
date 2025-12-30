<?php
declare(strict_types=1);

namespace controller;

use database\Server;

/**
 * Операции с БД и таблицами
 */
class Actions extends Base
{
    public function defaultAction(): array
    {
        global $msc;
        if (GET('users')) {
            return $this->userInfoAction();
        } elseif ($msc->table == '') {
            return $this->databaseActions();
        } else {
            return $this->tableActions();
        }
    }

    /**
     * Различная информация
     */
    public function userInfoAction(): array
    {
        global $msc;
        $msc->pageTitle = 'Различная информация';

        $users = $msc->getData('SELECT * FROM mysql.user');
        $grants = $msc->getData('SHOW GRANTS');
        $privileges = $msc->getData('SHOW PRIVILEGES');
        $engines = $msc->getData('SHOW ENGINES');

        return [
            'users' => $users,
            'grants' => $grants,
            'privileges' => $privileges,
            'engines' => $engines,
        ];
    }

    /**
     * Действия - БД
     */
    public function databaseActions()
    {
        global $msc;

        $msc->pageTitle = "Действия - БД";
        $DQuery = \UrlMaker::make('db', $msc->db, 's', 'actions');

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
            'ai' => $row['Auto_increment'] ?: '',
            'comment' => $row['Comment'] ?: '',
            'charset' => $row['Charset'] ?: '',
            'charsets' => Server::getCharsetArray(),
            'dbs' => Server::getDatabases(),
            'fields' => \DatabaseTable::getFields($msc->table, true),
        ];
    }
}
