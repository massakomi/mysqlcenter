<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

include_once(DIR_MYSQL . 'includes/Export.class.php');
classLoad('DatabaseManager');
classLoad('MSTable');

/**
 * Экспорт
 */

if (!defined('DIR_MYSQL')) {
    exit('Hacking attempt');
}

// 1. ИНИЦИАЛИЗАЦИЯ

$msct = new MSTable;

// шапка дампа
$dumpHeader =
'-- '.MS_APP_NAME.' SQL Экспорт
-- версия '.MS_APP_VERSION.'
--
-- Хост: '.DB_HOST.'
-- Время создания: '.date('j.m.Y, H-i').'
-- Версия сервера: '.PMA_MYSQL_STR_VERSION.'
-- Версия PHP: '.phpversion().'
-- 
-- БД: `'.$msc->db.'`
-- 

-- --------------------------------------------------------
';

// извлечение данных
if (count($_POST) > 0) {
    $isStruct  = (POST('export_struct') != '');
    $isData    = (POST('export_data') != '');
    $exType    = POST('export_option');
    $exWhere   = POST('export_where');
    $isDrop    = (POST('addDrop') != '');
    $addIfNot  = (POST('addIfNot') != '');
    $addAuto   = (POST('addAuto') != '');
    $addKav    = (POST('addKav') != '');
    $insFull   = (POST('insFull') != '');
    $insExpand = (POST('insExpand') != '');
    $insZapazd = (POST('insZapazd') != '');
    $insIgnor  = (POST('insIgnor') != '');
}

// 2. СПЕЦИАЛЬНЫЙ ЭКСПОРТ

if ($msc->page == 'exportSp') {
    $msc->pageTitle = 'Специальный экспорт данных';
    $drawForm = true;
    // Экспорт
    if (POST('exportSpecial') != null && $msc->db != null) {
        // Save
        if (POST('new') != null) {
            if (!$id_set = $msct->insertSet(POST('new'))) {
                return $msc->addMessage('Не смог добавить сет', null, MS_MSG_FAULT, mysqli_errorx());
            }
            foreach ($_POST['table'] as $key => $t){
                $struct = intval(isset($_POST['struct'][$key]));
                $data   = intval(isset($_POST['data'][$key]));
                $pk_top = intval($_POST['to'][$key]);
                $where_sql = $_POST['where'][$key];
                if ($data) {
                    $where = array();
                    $pri  = $_POST['field'][$key];
                    $from = intval($_POST['from'][$key]);
                    $to   = intval($_POST['to'][$key]);
                    if ($from < 1) {
                        $where []= "$pri >= $from";
                    }
                    if ($to > 0) {
                        $where []= "$pri <= $to";
                    }
                    if ($where_sql != '') {
                        $where []= $where_sql;
                    }
                    $where_sql = implode(' AND ', $where);
                }
                $msct->insertOption($id_set, $t, $struct, $data, $where_sql, $pk_top);
            }
            $msc->addMessage('Сет добавлен', null, MS_MSG_SUCCESS, mysqli_errorx());
        // Send
        } else {
            $drawForm = false;
            $exp = new MySQLExport();
            if (POST('addComment') != null) {
                $exp->setHeader($dumpHeader);
            }
            $exp->setDatabase($msc->db);
            $exp->setComments(POST('addComment') != null);
            $exp->setOptionsStruct($addIfNot, $addAuto, $addKav);
            $exp->setOptionsData($insFull, $insExpand, $insZapazd, $insIgnor);
            foreach ($_POST['table'] as $key => $t){
                $exp->setTable($t);
                $whereLocal = stripslashes($_POST['where'][$key]);
                if (isset($_POST['struct'][$key])) {
                    $exp->exportStructure($addDelim=1, $isDrop);
                }
                if (isset($_POST['data'][$key])) {
                    $where = array();
                    $pri  = $_POST['field'][$key];
                    $from = intval($_POST['from'][$key]);
                    $to   = intval($_POST['to'][$key]);
                    if ($from < 1) {
                        $where []= "$pri >= $from";
                    }
                    if ($to > 0) {
                        $where []= "$pri <= $to";
                    }
                    if ($whereLocal != '') {
                        $where []= $whereLocal;
                    }
                    $exp->exportData($exType, implode(' AND ', $where));
                }
            }
            if (intval(POST('export_to')) == 1) {
                echo $exp->send('zip');
            } else {
                echo $exp->send();
            }
        }
    }
    if ($drawForm) {
        $table = GET('table') ?: POST('table');
        // Отображение формы экспорта
        $cSet = $msct->getSetInfo(GET('set'));
        $data = [];
        if ($msc->db) {
            $result = $msc->query('SHOW TABLE STATUS FROM '.$msc->db);
            while ($o = mysqli_fetch_object($result)) {
                $o->Fields = getFields($o->Name);
                $data []= $o;
            }
        }
        $pageProps = [
            'dirImage' => MS_DIR_IMG,
            'structChecked' => true,
            'data' => $data,
            'configSet' => $cSet,
            'setsArray' => $msct->getSetsArray(),
            'fields' => $table ? getFields($table, true) : [],
        ];
        if (isajax()) {
            return $pageProps;
        }

        include(MS_DIR_TPL . 'exportSp.html');
    }

// 3. ОБЫЧНЫЙ ЭКСПОРТ

} else {
    $msc->pageTitle = 'Экспорт данных';

    $exportDb = POST('export_db');
    $array = POST('export_table');
    $optionsSelected = [];
    // 3.2.1. Создание
    if (is_array($array) && count($array) > 0 || is_array($exportDb) && count($exportDb) > 0) {
        // создание дампа
        $exp = new MySQLExport();
        $exp->setComments(POST('addComment') != '');
        $exp->setHeader($dumpHeader);
        $exp->setOptionsStruct($addIfNot, $addAuto, $addKav);
        $exp->setOptionsData($insFull, $insExpand, $insZapazd, $insIgnor);
        // Экспорт БД
        if ($exportDb && count($exportDb) > 0) {
            foreach ($exportDb as $db) {
                $exp->data .= "\r\n".'CREATE DATABASE `'.$db.'` DEFAULT CHARACTER SET '.MS_CHARACTER_SET.' COLLATE '.MS_COLLATION.';
USE `'.$db.'`;'."\r\n"."\r\n";
                $exp->setDatabase($db);
                $array = DatabaseManager::getTables($db);
                foreach ($array as $t) {
                    $exp->setTable($t);
                    $exp->startFull($isStruct, $isData, true, $isDrop, $exType, $exWhere);
                }
            }
        }
        // Экспорт таблицы
        else {
            $exp->setDatabase(GET('db'));
            foreach ($array as $t) {
                $exp->setTable($t);
                $exp->startFull($isStruct, $isData, true, $isDrop, $exType, $exWhere);
            }
        }
        // Send
        if (intval(POST('export_to')) == 1) {
            $file = $msc->table != '' ? $msc->table : $msc->db;
            $content = $exp->send('zip', $file);
        } else {
            $content = $exp->send();
        }
        if (isajax()) {
            return [
                'content' => $content
            ];
        }
        echo $content;
    }
    // 3.2.2. HTML форма экспорта
    else {
        $structChecked = ' defaultChecked';
        $whereCondition = null;
        $tableSelectMult = null;
        // 3.2.2.1. только если указана в запросе!
        if (GET('db') != '') {
            // массив таблиц из списка таблиц
            $tablesAll = DatabaseManager::getTables();
            $tables = isset($_POST['table']) ? $_POST['table'] : array();
            if (is_null($tables) || count($tables) == 0) {
                if ($msc->table == '') {
                    $optionsSelected = $tablesAll;
                } else {
                    $optionsSelected = array($msc->table);
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
        }
        // 3.2.2.2. Если бд не указана, то список БД
        else {
            $selectMultName  = 'export_db[]';
            $dbAll = Server::getDatabases();
            $optionsSelected = POST('databases');
            if ($optionsSelected == '' || count($optionsSelected)== 0) {
                $optionsSelected = array();
            }
            $optionsData = $dbAll;
        }
        $pageProps = [
            'dirImage' => MS_DIR_IMG,
            'structChecked' => $structChecked,
            'whereCondition' => $whereCondition,
            'selectMultName' => $selectMultName,
            'optionsData' => $optionsData,
            'optionsSelected' => $optionsSelected,
            'fields' => $_GET['table'] ? getFields($_GET['table'], true) : [],
        ];
        if (isajax()) {
            return $pageProps;
        }
        include(MS_DIR_TPL . 'export.htm.php');
    }

}
