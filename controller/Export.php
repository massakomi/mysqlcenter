<?php

declare(strict_types=1);

namespace controller;

use database\ExportCli;
use database\MSTable;
use database\Server;
use database\Table;

class Export extends Base
{
    /**
     * @return array<string>
     *
     * @throws \Exception
     */
    public function defaultAction(): array
    {
        global $msc;

        // извлечение данных
        $isStruct  = (POST('export_struct') != '');
        $isData    = (POST('export_data') != '');
        $exType    = POST('export_option');
        $exWhere   = POST('export_where');
        $isDrop    = (POST('addDrop') != '');

        // 2. СПЕЦИАЛЬНЫЙ ЭКСПОРТ
        if (GET('mode') == 'special') {
            return $this->specialExport();

            // 3. ОБЫЧНЫЙ ЭКСПОРТ
        } else {
            $msc->pageTitle = 'Экспорт данных';

            $exportDb = POST('export_db');
            $array = POST('export_table');
            $optionsSelected = [];
            // 3.2.1. Создание
            if (is_array($array) && count($array) > 0 || is_array($exportDb) && count($exportDb) > 0) {
                // создание дампа
                $config = (new \service\Config())->getConfig();
                $exp = new ExportCli($config);
                $exp->setHeader($this->dumpHeader());
                // Новые опции
                $exp->options['disableKeys'] = POST('disableKeys') != '';
                $exp->options['routines'] = POST('routines') != '';
                $exp->options['triggers'] = POST('triggers') != '';
                $exp->options['events'] = POST('events') != '';
                $exp->options['insertIgnore'] = POST('insIgnor') != '';
                $exp->options['disableTriggers'] = POST('disableTriggers') != '';
                $exp->options['columnInserts'] = POST('columnInserts') != '';
                $exp->options['extendedInsert'] = POST('insExpand') == '';
                $exp->options['comments'] = intval(POST('addComment')) == 1;
                // Экспорт БД
                if ($exportDb && count($exportDb) > 0) {
                    foreach ($exportDb as $db) {
                        $exp->data .= "\r\n".'CREATE DATABASE `'.$db.'` DEFAULT CHARACTER SET '.
                            MS_CHARACTER_SET.' COLLATE '.MS_COLLATION.';'."\r\n".' USE `'.$db.'`;'.
                            "\r\n\r\n";
                        $exp->setDatabase($db);
                        $array = Table::getTables($db);
                        foreach ($array as $t) {
                            $exp->setTable($t);
                            $exp->startFull($isStruct, $isData, $isDrop, $exType);
                        }
                    }
                } else {
                    // Экспорт таблицы
                    $exp->setDatabase(GET('db'));
                    foreach ($array as $t) {
                        $exp->setTable($t);
                        $exp->startFull($isStruct, $isData, $isDrop, $exType);
                    }
                }
                // Send
                if (intval(POST('export_to')) == 1) {
                    $file = $msc->table != '' ? $msc->table : $msc->db;
                    echo $exp->send('zip', $file);
                    exit;
                }

                return [
                    'content' => $exp->send(),
                ];
            } else {
                return $this->exportForm();
            }
        }
    }

    /**
     * HTML форма экспорта.
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    private function exportForm(): array
    {
        global $msc;
        $structChecked = ' defaultChecked';
        $whereCondition = null;
        // 3.2.2.1. только если указана в запросе!
        if (GET('db') != '') {
            // массив таблиц из списка таблиц
            $tablesAll = Table::getTables();
            $tables = $_POST['table'] ?? [];
            if (count($tables) == 0) {
                if ($msc->table == '') {
                    $optionsSelected = $tablesAll;
                } else {
                    $optionsSelected = [$msc->table];
                }
            } else {
                $optionsSelected = $tables;
            }
            // массив рядов из обзора таблицы
            if (POST('rowMulty') != '') {
                $_POST['row'] = array_map('urldecode', array_map('stripslashes', $_POST['row']));
                $whereCondition = '('.implode(') OR (', $_POST['row']).')';
            }
            // селектор таблиц мульти
            $selectMultName  = 'export_table[]';
            if ($whereCondition != null) {
                $structChecked = null;
            }
            $optionsData = $tablesAll;
        } else {
            // 3.2.2.2. Если бд не указана, то список БД
            $selectMultName  = 'export_db[]';
            $dbAll = Server::getDatabases();
            $optionsSelected = POST('databases');
            if ($optionsSelected == '' || count($optionsSelected) == 0) {
                $optionsSelected = [];
            }
            $optionsData = $dbAll;
        }

        return [
            'dirImage' => MS_DIR_IMG,
            'structChecked' => $structChecked,
            'whereCondition' => $whereCondition,
            'selectMultName' => $selectMultName,
            'optionsData' => $optionsData,
            'optionsSelected' => $optionsSelected,
            'fields' => GET('table') ? Table::getFieldNames(GET('table')) : [],
            'db_type' => $msc->driverName,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    private function specialExport(): array
    {
        global $msc;

        $exType    = POST('export_option');
        $isDrop    = (POST('addDrop') != '');
        $addIfNot  = (POST('addIfNot') != '');
        $addAuto   = (POST('addAuto') != '');
        $addKav    = (POST('addKav') != '');
        $insFull   = (POST('insFull') != '');
        $insExpand = (POST('insExpand') != '');
        $insZapazd = (POST('insZapazd') != '');
        $insIgnor  = (POST('insIgnor') != '');


        $msct = new MSTable();

        $msc->pageTitle = 'Специальный экспорт данных';
        // Экспорт
        if (POST('exportSpecial') != null && $msc->db != null) {
            // Save
            if (POST('new') != null) {
                if (!$id_set = $msct->insertSet(POST('new'))) {
                    $msc->error('Не смог добавить сет');

                    return [];
                }
                foreach ($_POST['table'] as $key => $t) {
                    $struct = intval(isset($_POST['struct'][$key]));
                    $data   = intval(isset($_POST['data'][$key]));
                    $pk_top = intval($_POST['to'][$key]);
                    $where_sql = $_POST['where'][$key];
                    if ($data) {
                        $where = $this->getWhere($key, $where_sql);
                        $where_sql = implode(' AND ', $where);
                    }
                    $msct::insertOption($id_set, $t, $struct, $data, $where_sql, $pk_top);
                }
                $msc->success('Сет добавлен');

                return [];

                // Send
            } else {
                $exp = new \database\Export();
                if (POST('addComment') != null) {
                    $exp->setHeader($this->dumpHeader());
                }
                $exp->setDatabase($msc->db);
                $exp->setComments(POST('addComment') != null);
                $exp->setOptionsStruct($addIfNot, $addAuto, $addKav);
                $exp->setOptionsData($insFull, $insExpand, $insZapazd, $insIgnor);
                foreach ($_POST['table'] as $key => $t) {
                    $exp->setTable($t);
                    $whereLocal = stripslashes($_POST['where'][$key]);
                    if (isset($_POST['struct'][$key])) {
                        $exp->exportStructure(true, $isDrop);
                    }
                    if (isset($_POST['data'][$key])) {
                        $where = $this->getWhere($key, $whereLocal);
                        $exp->exportData($exType, implode(' AND ', $where));
                    }
                }
                // Send
                if (intval(POST('export_to')) == 1) {
                    echo $exp->send('zip');
                    exit;
                }

                return [
                    'content' => $exp->send(),
                ];
            }
        }
        $table = GET('table') ?: POST('table');
        // Отображение формы экспорта
        $cSet = $msct::getSetInfo(GET('set'));
        $data = [];
        if ($msc->db) {
            $result = $msc->getData('SHOW TABLE STATUS FROM '.$msc->db);
            foreach ($result as $o) {
                $o['Fields'] = Table::getFields($o['Name']);
                $data[] = $o;
            }
        }

        return [
            'dirImage' => MS_DIR_IMG,
            'structChecked' => true,
            'data' => $data,
            'configSet' => $cSet,
            'setsArray' => $msct->getSetsArray(),
            'fields' => $table ? Table::getFieldNames($table) : [],
        ];
    }

    /**
     * шапка дампа.
     *
     * @throws \Exception
     */
    private function dumpHeader(): string
    {
        global $msc;

        return '-- SQL Экспорт
--
-- Хост: '.$msc->host.'
-- Время создания: '.date('j.m.Y, H-i').'
-- Версия сервера: '.Server::getServerVersion().'
-- Версия PHP: '.phpversion().'
-- 
-- БД: `'.$msc->db.'`
-- 

-- --------------------------------------------------------
';
    }

    /**
     * @return array<string>
     */
    private function getWhere(int|string $key, mixed $where_sql): array
    {
        $where = [];
        $pri = $_POST['field'][$key];
        $from = intval($_POST['from'][$key]);
        $to = intval($_POST['to'][$key]);
        if ($from < 1) {
            $where[] = "$pri >= $from";
        }
        if ($to > 0) {
            $where[] = "$pri <= $to";
        }
        if ($where_sql != '') {
            $where[] = $where_sql;
        }

        return $where;
    }
}
