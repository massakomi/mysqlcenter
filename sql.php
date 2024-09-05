<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * SQL запрос в БД
 */

$msc->pageTitle = 'SQL запрос в БД';

// Запрос из файла
if (isset($_FILES['sqlFile']) && $_FILES['sqlFile']['size'] > 0) {
    if ($_FILES['sqlFile']['size'] <= MAX_UPLOAD_SIZE) {
        if (POST('compress') == 'zip' || substr($_FILES['sqlFile']['name'], -3) == 'zip') {
            if (!function_exists('zip_open')) {
                $msc->addMessage('Расширение zip не включено. Не могу распаковать файл', null, MS_MSG_FAULT);
                return null;
            }
            $zip = zip_open($_FILES['sqlFile']['tmp_name']);
            if ($zip) {
                $s = null;
                while ($zip_entry = zip_read($zip)) {
                    if (zip_entry_open($zip, $zip_entry, "r")) {
                        $s .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                        zip_entry_close($zip_entry);
                    }
                }
                zip_close($zip);
            }

        } elseif (POST('compress') == 'csv') {

            $fields = getFields('');

            $data = file_get_contents($_FILES['sqlFile']['tmp_name']);
            $data = iconv('windows-1251', 'utf-8', $data);
            $data = explode("\n", $data);
            if (0) {
                $data = array_unique($data);
            }
            foreach ($data as $k => $v) {
                $row = array_map('mysqli_escape_stringx', explode(';', trim($v)));
                echo '<br />INSERT INTO s_products_text (brand, name) VALUES ("'.implode('","', $row).'");';
            }

        } elseif (POST('compress') == 'excel') {

            //include_once 'includes/excel_reader.php';

            //$data = new Spreadsheet_Excel_Reader($_FILES['sqlFile']['tmp_name'], false);

            foreach ($data->boundsheets as $k => $v) {
                echo '<a href="?sheet='.$k.'">'.$v['name'].'</a> &nbsp; ';
            }
            // excel_reader.php

            $sheet = 1;

            echo '<br /><br />';
            //echo '<pre>'; print_r($data->sheets[$_GET['sheet']]); echo '</pre>';
            //$sheet = array();

            echo '
            <table class="optionstable">';
            foreach ($data->sheets[$sheet]['cells'] as $cell => $values) {
                //$sheet [][]= ;
                $str = trim(implode(' ', $values));
                if (empty($str)) {
                    continue;
                }
                echo '<tr>';
                //array_shift($values);
                foreach ($values as $k => $v) {
                   echo '<td>'.$v.'</td>';
                }
                echo '</tr>';
            }
            echo '</table>';

            unset($data->sheets[$sheet]['cells']);

            echo '<pre>'; print_r($data->sheets[$sheet]); echo '</pre>';



        } else {
            $mime = '';
            if (POST('compress') == '') {
                $mime = 'text/plain';
            } elseif (POST('compress') == 'gzip') {
                $mime = 'application/x-gzip';
            }
            $s = readZipFile($_FILES['sqlFile']['tmp_name'], $mime);
        }
        // Применяем кодировку если надо
        if (POST('sqlFileCharset') != null && POST('sqlFileCharset') != 'utf8') {
            $msc->query("SET NAMES '".POST('sqlFileCharset')."'");
        }
        $log = strlen($s) < 10000;
        execSql(GET('db'), $s, $log);
        if (POST('sqlFileCharset') != null && POST('sqlFileCharset') != 'utf8') {
            $msc->query("SET NAMES 'utf8'");
        }
    } else {
        $msc->addMessage('Размер файла превышает максимально допустимый', null, MS_MSG_FAULT);
    }
// Запрос из ПОСТа
} elseif (isset($_POST['sql']) && $_POST['sql'] != '') {
    $a = ini_get("magic_quotes_gpc");
    if (is_numeric($a)) {
        $_POST['sql'] = stripslashes($_POST['sql']);
    }
    if (trim(mb_strtolower(substr($_POST['sql'], 0, 6))) == 'select') {
        // редирект на лист
        $msc->pageTitle = 'Обзор таблицы ';
        $msc->page = 'tbl_data';
        $directSQL = $_POST['sql'];
        include_once(DIR_MYSQL . 'tbl_data.php');
        return null;
    } else {
        $log = strlen($_POST['sql']) < 10000;
        execSql(GET('db'), $_POST['sql'], $log);
    }
}

$pageProps = [
    'maxUploadSize' => MAX_UPLOAD_SIZE,
    'maxSize' => round(MAX_UPLOAD_SIZE / (1024 * 1024), 2),
    'charsets' => getCharsetArray(),
    'sql' => POST('sql')
];
if (isajax()) {
    return $pageProps;
}

// Вывод
include(MS_DIR_TPL . 'sql.htm.php');
