<?php

declare(strict_types=1);

namespace controller;

use database\Table;
use service\UrlMaker;

/**
 *
 */
class TblStruct extends Base
{
    public function defaultAction(): array
    {
        global $msc;

        if (GET('action') == 'add_key') {
            return $this->addKeyAction();
        }

        if ($msc->table == '') {
            $msc->error('Не указана таблица в запросе');
            return [];
        }
        $fields = Table::getFields($msc->table);
        if (!$fields) {
            $msc->error("Таблицы $msc->table не существует");
            return [];
        }
        $msc->pageTitle = 'Структура таблицы ' . $msc->table;

        $dataKeys = $foreignKeys = [];
        if (GET('keys')) {
            [$dataKeys, $foreignKeys] = $this->getKeys();
        }

        return [
            'db' => $msc->db,
            'table' => $msc->table,
            'addKeyUrl' => UrlMaker::make('s', 'tbl_struct', 'action', 'add_key'),
            'showKeysUrl' => UrlMaker::make('s', 'tbl_struct', 'keys', 1),
            'addTableUrl' => UrlMaker::make('s', 'tbl_add'),
            'data' => $fields,
            'showKeys' => GET('keys'),
            'dataKeys' => $dataKeys,
            'foreignKeys' => $foreignKeys,
            'dataDetails' => $msc->driver->getTableDetailsWithComments($msc->table),
            'sqlCreateTable' => $msc->driver->sqlCreateTable($msc->table),
            'dirImage' => MS_DIR_IMG
        ];
    }

    /**
     * @return array
     */
    public function addKeyAction(): array
    {
        global $msc;
        $fieldRows = ['' => ''];
        $fields = Table::getFields($msc->table);
        foreach ($fields as $field) {
            $fieldRows [$field->Field] = "$field->Field [$field->Type]";
        }
        $msc->pageTitle = 'Добавить ключи к таблице "' . $msc->table . '"';

        return [
            'fieldRows' => $fieldRows,
            'keyName' => POST('keyName'),
            'postType' => POST('keyType'),
            'dirImage' => MS_DIR_IMG,
        ];
    }

    /**
     * @return array
     */
    private function getKeys(): array
    {
        global $msc;
        $constraints = $msc->driver->getConstraints($msc->table);
        $dataKeys = $msc->driver->getKeys($msc->table, true);
        $foreignKeys = $constraints['FOREIGN KEY'] ?? [];
        return [$dataKeys, $foreignKeys];
    }
}
