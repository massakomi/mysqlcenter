<?php

declare(strict_types=1);

namespace controller;

/**
 *
 */
class Sql extends Base
{
    /**
     * @return array<string, mixed>
     * @throws \Exception
     */
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
                } else {
                    $mime = '';
                    if ($type == '') {
                        $mime = 'text/plain';
                    } elseif ($type == 'gzip') {
                        $mime = 'application/x-gzip';
                    }
                    $sql = $this->readZipFile($_FILES['sqlFile']['tmp_name'], $mime);
                }
                $sql = strval($sql);

                // Применяем кодировку если надо
                if (POST('sqlFileCharset') != null && POST('sqlFileCharset') != 'utf-8') {
                    $sql = mb_convert_encoding($sql, 'UTF-8', POST('sqlFileCharset')) ?: $sql;
                }
                $log = strlen($sql) < 10000;
                $this->execSql($db, $sql, $log);
                if (POST('sqlFileCharset') != null && POST('sqlFileCharset') != 'utf-8') {
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
     * Ускоренное выполнение большого кол-ва запросов с логом
     *
     * @param string $db База данных
     * @param string $sql SQL запрос (передаётся по ссылке, чтобы снизить расход памяти)
     * @param bool $log
     * @return void
     * @throws \Exception
     * @package msc
     */
    private function execSql(string $db, string &$sql, bool $log = true): void
    {
        global $msc;
        $mysqlGenerationTime0 = round(array_sum(explode(" ", microtime())), 10);
        if (!$msc->selectDb($db)) {
            $msc->error('Не смог выбрать базу данных');
            return;
        }
        if ($log) {
            logInFile($sql, $msc->db);
        }
        $msc->exceptionOnError = false;
        if ($msc->execPdo($sql)) {
            $msc->success('Запрос выполнен без ошибок');
        } else {
            $msc->error('Запрос выполнен с ошибками');
        }
        $mysqlGenerationTime = round(round(array_sum(explode(" ", microtime())), 10) - $mysqlGenerationTime0, 5);
        $msc->success("Выполнено за $mysqlGenerationTime с.");
        $msc->success("Затронуто рядов: $msc->affectedRows");
    }

    /**
     * Считывает (и распаковывает сжатый) файл в строку
     *
     * @param string $path Путь к файлу
     * @param string $mime MIME тип файла, иначе определяется автоматически
     * @return string|null string контент файла либо boolean FALSE в случае ошибок
     * @package file
     */
    private function readZipFile(string $path, string $mime = ''): ?string
    {
        global $msc;
        if (!file_exists($path)) {
            return null;
        }
        $content = '';
        switch ($mime) {
            case '':
                $test = file_get_contents_bytes($path, 3) ?: '';
                if (mb_substr($test, 0, 1) == chr(31) &&
                    mb_substr($test, 1, 1) == chr(139)) {
                    return $this->readZipFile($path, 'application/x-gzip');
                }
                if ($test == 'BZh') {
                    return $this->readZipFile($path, 'application/x-bzip');
                }
                return $this->readZipFile($path, 'text/plain');
            case 'zip':
                $zip = new \ZipArchive();

                if ($zip->open($path) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $content .= $zip->getFromIndex($i) . ';';
                    }
                    $zip->close(); // Close the archive
                } else {
                    $msc->error('Failed to open zip file, error code: ' . $zip->status);
                }

                break;
            case 'text/plain':
                $content = file_get_contents($content);
                break;
            case 'application/x-gzip':
                if (function_exists('gzopen')) {
                    $file = @gzopen($path, 'rb');
                    if (!$file) {
                        return null;
                    }
                    while (!gzeof($file)) {
                        $content .= gzgetc($file);
                    }
                    gzclose($file);
                } else {
                    return null;
                }
                break;
            case 'application/x-bzip':
                if (@function_exists('bzdecompress')) {
                    $content = file_get_contents_bytes($path);
                    $content = bzdecompress($content ?: '');
                } else {
                    return null;
                }
                break;
            default:
                return null;
        }
        if (!is_string($content)) {
            return null;
        }
        return $content;
    }
}