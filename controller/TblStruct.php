<?php

namespace controller;

/**
 *
 */
class TblStruct extends Base
{
    public function defaultAction(): array
    {
        global $msc, $umaker;

        if (GET('action') == 'add_key') {
            return $this->addKeyAction();
        }

        if ($msc->table == '') {
            $msc->error('Не указана таблица в запросе');
            return [];
        }
        $fields = \DatabaseTable::getFields($msc->table);
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
            'addKeyUrl' => $umaker->make('s', 'tbl_struct', 'action', 'add_key'),
            'showKeysUrl' => $umaker->make('s', 'tbl_struct', 'keys', 1),
            'addTableUrl' => $umaker->make('s', 'tbl_add'),
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
        $fields = \DatabaseTable::getFields($msc->table);
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
        $a = $msc->table != '' ? $msc->getData('
                SELECT i.*, k.*  FROM information_schema.TABLE_CONSTRAINTS i
                LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME 
                WHERE  i.CONSTRAINT_TYPE = \'FOREIGN KEY\' AND i.TABLE_SCHEMA = \'' . $msc->db . '\'
                AND i.TABLE_NAME = \'' . $msc->table . '\'
                GROUP BY k.CONSTRAINT_NAME
            ') : [];
        $foreignKeys = [];
        foreach ($a as $k => $v) {
            $foreignKeys[$v['COLUMN_NAME']] = $v;
        }
        $dataKeys = $msc->table != '' ? $msc->getData('SHOW KEYS FROM `' . $msc->table . '`') : [];
        return [$dataKeys, $foreignKeys];
    }

}
