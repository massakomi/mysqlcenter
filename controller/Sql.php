<?php

declare(strict_types=1);

namespace controller;

/**
 *
 */
class Sql extends Base
{
    public function defaultAction(): array
    {
        global $msc;

        $msc->pageTitle = 'SQL запрос в БД';
        $db = GET('db') ?: POST('db');
        $type = POST('compress');

        // Запрос из файла
        if (isset($_FILES['sqlFile']) && $_FILES['sqlFile']['size'] > 0) {
            if ($_FILES['sqlFile']['size'] <= MAX_UPLOAD_SIZE) {
                if ($type == 'zip' || substr($_FILES['sqlFile']['name'], -3) == 'zip') {
                    $sql = $this->readZipFile($_FILES['sqlFile']['tmp_name'], 'zip');
                } elseif ($type == 'csv') {
                    $this->csvTest();
                } elseif ($type == 'excel') {
                    $this->excelTest();
                } else {
                    $mime = '';
                    if ($type == '') {
                        $mime = 'text/plain';
                    } elseif ($type == 'gzip') {
                        $mime = 'application/x-gzip';
                    }
                    $sql = $this->readZipFile($_FILES['sqlFile']['tmp_name'], $mime);
                }

                // Применяем кодировку если надо
                if (POST('sqlFileCharset') != null && POST('sqlFileCharset') != 'utf8') {
                    $sql = mb_convert_encoding($sql, 'UTF-8', POST('sqlFileCharset'));
                }
                $log = strlen($sql) < 10000;
                $this->execSql($db, $sql, $log);
                if (POST('sqlFileCharset') != null && POST('sqlFileCharset') != 'utf8') {
                    $msc->execPdo("SET NAMES 'utf8'");
                }
            } else {
                $msc->error('Размер файла превышает максимально допустимый');
            }
        // Запрос из ПОСТа
        } elseif (isset($_POST['sql']) && $_POST['sql'] != '') {
            $a = ini_get("magic_quotes_gpc");
            if (is_numeric($a)) {
                $_POST['sql'] = stripslashes($_POST['sql']);
            }
            $log = strlen($_POST['sql']) < 10000;
            $this->execSql($db, $_POST['sql'], $log);
        }


        return [
            'maxUploadSize' => MAX_UPLOAD_SIZE,
            'maxSize' => round(MAX_UPLOAD_SIZE / (1024 * 1024), 2),
            'charsets' => mb_list_encodings(),
            //'sql' => POST('sql')
        ];
    }

    /**
     * {}
     */
    private function csvTest()
    {
        $data = file_get_contents($_FILES['sqlFile']['tmp_name']);
        $data = iconv('windows-1251', 'utf-8', $data);
        $data = explode("\n", $data);
        foreach ($data as $k => $v) {
            $row = array_map(function ($value) {
                global $pdo;
                return $pdo->quote($value);
            }, explode(';', trim($v)));
            echo '<br />INSERT INTO s_products_text (brand, name) VALUES ("' . implode('","', $row) . '");';
        }
    }

    /**
     * {}
     */
    private function excelTest()
    {
        //include_once 'includes/excel_reader.php';

        //$data = new Spreadsheet_Excel_Reader($_FILES['sqlFile']['tmp_name'], false);
        $data = [];

        foreach ($data->boundsheets as $k => $v) {
            echo '<a href="?sheet=' . $k . '">' . $v['name'] . '</a> &nbsp; ';
        }
        // excel_reader.php

        $sheet = 1;

        echo '<br /><br />';
        //echo '<pre>'; print_r($data->sheets[$_GET['sheet']]); echo '</pre>';
        //$sheet = [];

        echo '
            <table class="contentTable">';
        foreach ($data->sheets[$sheet]['cells'] as $cell => $values) {
            //$sheet [][]= ;
            $str = trim(implode(' ', $values));
            if (empty($str)) {
                continue;
            }
            echo '<tr>';
            //array_shift($values);
            foreach ($values as $k => $v) {
                echo '<td>' . $v . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';

        unset($data->sheets[$sheet]['cells']);

        echo '<pre>';
        print_r($data->sheets[$sheet]);
        echo '</pre>';
    }

    /**
     * Ускоренное выполнение большого кол-ва запросов с логом
     *
     * @param string База данных
     * @param string SQL запрос (передаётся по ссылке, чтобы снизить расход памяти)
     * @throws \Exception
     * @package msc
     */
    private function execSql($db, &$sql, $log = true)
    {
        global $msc;
        $mysqlGenerationTime0 = round(array_sum(explode(" ", microtime())), 10);
        if (!$msc->selectDb($db)) {
            return $msc->error('Не смог выбрать базу данных');
        }
        if ($log) {
            logInFile($sql, $msc->db);
        }
        $sql = str_replace("\r\n", "\n", $sql);
        $array = explode(";\n", $sql);
        $errors = [];
        $c = 0;
        $affected = 0;
        $count = count($array);
        $msc->exceptionOnError = false;
        for ($i = 0; $i < $count; $i++) {
            $q = trim($array[$i]);
            if (empty($q) || (strpos($q, '--') === 0 && strpos($q, "\n") === false)) {
                continue;
            }
            $c++;
            if (!$msc->execPdo($q)) {
                $errors [] = $msc->error . ' (' . substr($q, 0, 100) . ')';
            } else {
                $affected += $msc->affectedRows;
            }
        }
        $fault = count($errors);
        $succ = $c - $fault;
        $info = " $succ запросов выполнено, $fault неудач. ";
        if (count($errors) == 0) {
            $msc->success('Запрос выполнен без ошибок - ' . $info);
        } else {
            $msc->error('Запрос выполнен с ошибками' . $info);
            $msc->error(implode('<br />', $errors));
        }
        $mysqlGenerationTime = round(round(array_sum(explode(" ", microtime())), 10) - $mysqlGenerationTime0, 5);
        $msc->success("Выполнено за $mysqlGenerationTime с.");
        $msc->success("Затронуто рядов: $affected");
        return true;
    }

    /**
     * Считывает (и распаковывает сжатый) файл в строку
     *
     * @param string   Путь к файлу
     * @param string   MIME тип файла, иначе определяется автоматически
     * @return  mixed    string контент файла либо boolean FALSE в случае ошибок
     * @package file
     */
    private function readZipFile($path, $mime = '')
    {
        global $msc;
        if (!file_exists($path)) {
            return false;
        }
        switch ($mime) {
            case '':
                $file = @fopen($path, 'rb');
                if (!$file) {
                    return false;
                }
                $test = fread($file, 3);
                fclose($file);
                if ($test[0] == chr(31) && $test[1] == chr(139)) {
                    return $this->readZipFile($path, 'application/x-gzip');
                }
                if ($test == 'BZh') {
                    return $this->readZipFile($path, 'application/x-bzip');
                }
                return $this->readZipFile($path, 'text/plain');
            case 'zip':
                $zip = new \ZipArchive();

                if ($zip->open($path) === true) {
                    $content = '';
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $content .= $zip->getFromIndex($i) . ';';
                    }
                    $zip->close(); // Close the archive
                } else {
                    $msc->error('Failed to open zip file, error code: ' . $zip->status);
                }

                break;
            case 'text/plain':
                $file = @fopen($path, 'rb');
                if (!$file) {
                    return false;
                }
                $content = fread($file, filesize($path));
                fclose($file);
                break;
            case 'application/x-gzip':
                if (function_exists('gzopen')) {
                    $file = @gzopen($path, 'rb');
                    if (!$file) {
                        return false;
                    }
                    $content = '';
                    while (!gzeof($file)) {
                        $content .= gzgetc($file);
                    }
                    gzclose($file);
                } else {
                    return false;
                }
                break;
            case 'application/x-bzip':
                if (@function_exists('bzdecompress')) {
                    $file = @fopen($path, 'rb');
                    if (!$file) {
                        return false;
                    }
                    $content = fread($file, filesize($path));
                    fclose($file);
                    $content = bzdecompress($content);
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }
        return $content;
    }
}
