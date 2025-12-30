<?php
declare(strict_types=1);

namespace controller;

/**
 *
 */
class DbCompare extends Base
{
    public function defaultAction(): array
    {
        global $msc;

        $databases = [];
        if (POST('dbs')) {
            $databases = explode(';', POST('dbs'));
        }
        if (!$databases) {
            $databases = POST('databases');
        }
        if (!$databases || count($databases) < 2) {
            $msc->error('Вы не выбрали базы данных для сравнения');
            return [];
        }
        $msc->pageTitle = 'Сравнение баз данных ' . implode(', ', $databases);

        return $this->pageProps($databases);
    }

    /**
     * @param $databases
     * @return array
     */
    public function pageProps($databases): array
    {
        global $msc;

        // Создание начальных массивов
        $dbArray = [];
        foreach ($databases as $database) {
            $data = $msc->driver->getTables($database);
            if (!$data) {
                $msc->error("Не нашел таблиц в $database");
                return [];
            }
            foreach ($data as $row) {
                $dbArray[$database][$row->Name] = $row;
            }
        }

        $exportArray = [];
        $export = new \Export();
        $export->setComments(0);
        $export->setOptionsStruct(0, $addAuto = 0, 0);
        foreach ($dbArray as $db => $tables) {
            foreach ($tables as $table => $values) {
                $export->data = null;
                $export->setDatabase($db);
                $export->setTable($table);
                $exportData = $export->exportStructure(0, 0);
                $exportData = str_replace(' PACK_KEYS=0', '', $exportData);
                $exportData = preg_replace('~COMMENT=".*"~U', '', $exportData);
                $exportArray [$db][$table] = $exportData;
            }
        }
        return compact('databases', 'dbArray', 'exportArray');
    }
}
