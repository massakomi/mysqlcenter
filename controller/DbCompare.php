<?php

declare(strict_types=1);

namespace controller;

use database\ExportCli;

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
        $msc->pageTitle = 'Сравнение баз данных '.implode(', ', $databases);

        return $this->pageProps($databases);
    }

    /**
     * @param array<string> $databases
     *
     * @return array<string, mixed>
     *
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
        $export = new ExportCLI($msc->config->getConfig());
        foreach ($dbArray as $db => $tables) {
            foreach ($tables as $table => $values) {
                $export->data = '';
                $export->setDatabase($db);
                $export->setTable($table);
                $export->startFull(true, false, false, '');
                $exportArray[$db][$table] = $export->data;
            }
        }

        return compact('databases', 'dbArray', 'exportArray');
    }
}
