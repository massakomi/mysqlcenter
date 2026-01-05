<?php

declare(strict_types=1);

namespace controller;

/**
 *
 */
class DbCompare extends Base
{
    /**
     * @return array<string, mixed>
     */
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
     * @param array<string> $databases
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function pageProps(array $databases): array
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
        $export = new \database\Export();
        $export->setComments(false);
        $export->setOptionsStruct(false, false, false);
        foreach ($dbArray as $db => $tables) {
            foreach ($tables as $table => $values) {
                $export->data = null;
                $export->setDatabase($db);
                $export->setTable($table);
                $exportData = $export->exportStructure(false, false);
                $exportData = str_replace(' PACK_KEYS=0', '', $exportData);
                $exportData = preg_replace('~COMMENT=".*"~U', '', $exportData);
                $exportArray [$db][$table] = $exportData;
            }
        }
        return compact('databases', 'dbArray', 'exportArray');
    }
}
