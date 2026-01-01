<?php

declare(strict_types=1);

namespace database;

/**
 * Библиотека общих функций по экспорту таблиц БД
 */
class Export
{
    public $db;
    public $table;
    public $tableb;
    public $data;
    public $tableStructure = [];
    public $comments = true;
    public $fields = [];

    public $addIfNot = false;
    public $addAuto = true;
    public $addKav = true;

    public $insFull = false;
    public $insExpand = false;
    public $insZapazd = false;
    public $insIgnor = false;

    /**
     * Позволяет сразу установить опции экспорта
     */
    public function __construct($db = null, $table = null, $header = null)
    {
        $this->table = $this->tableb = $table;
        $this->db = $db;
        $this->data = $header;
    }

    /**
     * Запускает комплексный процесс экспорта
     *
     * $isStruct
     * $isData
     * $addDelim
     * $addDrop
     * $type
     * $where
     */
    public function startFull(
        $isStruct = true,
        $isData = true,
        $addDelim = true,
        $addDrop = false,
        $type = 'INSERT',
        $where = null
    ) {
        if ($isStruct) {
            $this->exportStructure($addDelim, $addDrop);
        }
        if ($isData) {
            $this->exportData($type, $where);
        }
        return $this->get();
    }

    // Установить текущую базу данных
    public function setDatabase($a)
    {
        global $msc;
        if ($this->db != $a) {
            $this->db = $a;
            $msc->selectDb($this->db);
        }
    }

    // Установить текущую таблицу
    public function setTable($a)
    {
        $this->table = $a;
        if ($this->addKav) {
            $this->tableb = "`$a`";
        } else {
            $this->tableb = $a;
        }
    }

    // Установить шапку к дампу
    public function setHeader($a)
    {
        if ($this->comments) {
            $this->data .= $a;
        }
    }

    // Добавлять или нет комментарии
    public function setComments($a)
    {
        $this->comments = (bool)$a;
    }

    /**
     * Установить некоторые опции экспорта структуры
     */
    public function setOptionsStruct($addIfNot, $addAuto, $addKav)
    {
        $this->addIfNot = $addIfNot;
        $this->addAuto = $addAuto;
        $this->addKav = $addKav;
    }

    /**
     * УСтавноить некоорые опции экспорта данных
     */
    public function setOptionsData($insFull, $insExpand, $insZapazd, $insIgnor)
    {
        $this->insFull = $insFull;
        $this->insExpand = $insExpand;
        $this->insZapazd = $insZapazd;
        $this->insIgnor = $insIgnor;
    }

    /**
     * Получить полный текст дампа
     * @ $clear - очистить объект (экономия памяти)
     */
    public function get()
    {
        return $this->data;
    }

    /**
     * Заворачивает дамп в нужный вид и отправляет
     *
     * @ $type - тип отправки, значения:
     *   'textarea' - создаёт форму
     *   'zip' - создаёт архив и отправляет
     * @ $file - имя файла дампа для типа 'zip'
     */
    public function send($type = 'textarea', $file = null)
    {
        return $this->sendSQLDamp($type, $file);
    }


    // ниже - внутренние функции, реализация

    /**
     * Возврвщает дамп структуры таблицы (sql запрос создания таблицы)
     * @$this->table - имя таблицы
     * @$addDrop - добавить к запросу удаление таблицы + форматировать через ;
     * @$this->comments - добавить комментарий
     */
    public function exportStructure($addDelim = true, $addDrop = false)
    {
        global $msc;
        $delim = ";\r\n";
        $wr = "\r\n";
        $tab = '  ';
        $dump = null;
        if ($this->comments) {
            $dump .= $wr . '--' . $wr . '-- Структура таблицы ' . $this->table . $wr . '--' . $wr;
        }
        $ife = null;
        if ($addDrop) {
            $if = null;
            if ($this->addIfNot) {
                $if = 'IF EXISTS ';
            }
            $dump .= 'DROP TABLE ' . $if . $this->tableb . $delim;
        }
        if ($this->addIfNot) {
            $ife = 'IF NOT EXISTS ';
        }
        $dump .= 'CREATE TABLE ' . $ife . $this->tableb . ' (' . $wr . $tab;

        // дамп полей
        $result = $msc->driver->getFields($this->table);
        if (!$result) {
            return null;
        }
        $fields = [];
        $this->fields = [];
        foreach ($result as $row) {
            $this->fields [] = $row;
            if ($this->addKav) {
                $field_info = '`' . $row->Field . '` ' . $row->Type;
            } else {
                $field_info = $row->Field . ' ' . $row->Type;
            }
            if ($row->Null != 'YES') {
                $field_info .= ' NOT NULL';
            }

            if ($row->Type == 'timestamp') {
                if ($row->Default != '') {
                    $row->Default = $row->Default == 'CURRENT_TIMESTAMP' ? $row->Default : '\'' . $row->Default . '\'';
                    $field_info .= ' default ' . $row->Default;
                }
            } elseif ($row->Default != null || ($row->Null != 'YES' && !strchr($row->Type, 'text'))) {
                if (!stristr($row->Extra, 'auto')) {
                    if ($row->Null != 'YES' && $row->Default == '') {
                    } else {
                        $field_info .= ' default \'' . $row->Default . '\'';
                    }
                }
            } elseif (!strchr($row->Type, 'text')) {
                $field_info .= ' default NULL';
            }
            if ($row->Extra != '') {
                $field_info .= ' ' . $row->Extra;
            }
            $fields [] = $field_info;
        }
        // ключи
        $keys = [];
        $keys['PRI'] = $keys['UNI'] = $keys['MUL'] = $keys['FULL'] = [];
        $parts = [];
        $result = $msc->driver->getKeys($this->table, true);
        $x = $this->addKav ? '`' : '';
        foreach ($result as $row) {
            $row->Column_name = $x . $row->Column_name . $x;
            if ($row->Sub_part > 0) {
                $row->Column_name .= '(' . $row->Sub_part . ')';
            }
            if (str_contains($row->Key_name, 'PRIMARY')) {
                $keys['PRI'][] = $row->Column_name;
            } elseif ($row->Index_type == 'FULLTEXT') {
                $keys['FULL'][$row->Key_name][] = $row->Column_name;
            } elseif ($row->Non_unique == '0') {
                $keys['UNI'][$row->Key_name][] = $row->Column_name;
            } else {
                $keys['MUL'][$row->Key_name][] = $row->Column_name;
            }
        }
        // обработка ключей
        $a = [];
        if (count($keys['PRI']) > 0) {
            $a [] = "PRIMARY KEY  (" . implode(",", $keys['PRI']) . ")";
        }
        if (count($keys['UNI']) > 0) {
            foreach ($keys['UNI'] as $k => $c) {
                $a [] = "UNIQUE KEY $x" . $k . "$x (" . implode(",", $c) . ")";
            }
        }
        if (count($keys['MUL']) > 0) {
            foreach ($keys['MUL'] as $k => $c) {
                $a [] = "KEY $x" . $k . "$x (" . implode(",", $c) . ")";
            }
        }
        if (count($keys['FULL']) > 0) {
            foreach ($keys['FULL'] as $k => $c) {
                $a [] = "FULLTEXT KEY $x" . $k . "$x (" . implode(",", $c) . ")";
            }
        }
        // Загрузка
        $dump .= implode(',' . $wr . $tab, $fields);
        if (count($a) > 0) {
            $dump .= ',' . $wr . $tab;
        }
        $dump .= implode(',' . $wr . $tab, $a) . $wr;
        // кодировка, тип, автоинкремент
        $ai = null;
        $comment = null;
        $charset = 'utf8';
        $engine = 'MyISAM';
        $pack = null;
        if (!isset($this->tableStructure[$this->db])) {
            $result = $msc->driver->getTables();
            $this->tableStructure[$this->db] = [];
            foreach ($result as $row) {
                $this->tableStructure [$this->db][] = $row;
            }
        }
        foreach ($this->tableStructure[$this->db] as $row) {
            if ($row->Name == $this->table) {
                $ai = $row->Auto_increment;
                $charset = $row->Collation;
                $comment = $row->Comment;
                $engine = $row->Engine;
                $pack = $row->Create_options;
                break;
            }
        }
        if (!$this->addAuto || $ai == null) {
            $ai = null;
        } else {
            $ai = ' AUTO_INCREMENT=' . $ai . ' ';
        }
        if (strlen($pack) > 0) {
            $pack = ' ' . $pack;
        }
        if ($comment != null) {
            $comment = ' COMMENT="' . $comment . '"';
        }
        if (strchr($charset, '_')) {
            $charset = str_replace(strchr($charset, '_'), '', $charset);
        }
        if ($addDelim) {
            $dump .= ") ENGINE=$engine DEFAULT CHARSET=$charset$pack$ai$comment" . $delim;
        } else {
            $dump .= ") ENGINE=$engine DEFAULT CHARSET=$charset$pack$ai$comment" . $wr;
        }
        return $this->data .= $dump;
    }


    /**
     * Возврвщает дамп данных таблицы (sql запрос )
     * @param string   тип экспорта (INSERT-REPLACE-UPDATE)
     * @param string   SQL условие
     * @param boolean  пропускать ли поля с auto_increment
     */
    public function exportData($type = 'INSERT', $where = null, $skipAi = false)
    {
        global $msc, $pdo;
        $memory_limit = (intval(ini_get('memory_limit')) * 1024 * 1024) / 2;
        $delim = ";\r\n";
        $wr = "\r\n";
        $tab = '    ';
        if (is_null($where) || strlen(trim($where)) < 2) {
            $sql = "SELECT * FROM $this->tableb";
        } else {
            if (stristr($where, 'WHERE ')) {
                $sql = "SELECT * FROM $this->tableb $where";
            } else {
                $sql = "SELECT * FROM $this->tableb WHERE $where";
            }
        }
        if (!$q_result = $msc->fetchPdo($sql)) {
            return null;
        }
        $dump = null;
        // поля
        $exportedFields = [];
        if ($_POST['fields']) {
            $exportedFields = $_POST['fields'];
        }
        $f = $this->getFields($this->table);
        foreach ($f as $k => $v) {
            if ($exportedFields && !in_array($v->Field, $exportedFields) && ($type != 'UPDATE' || $v->Key != 'PRI')) {
                unset($f[$k]);
            }
        }
        $fnames = [];
        foreach ($f as $i => $v) {
            if ($skipAi && $v->Extra != '') {
                continue;
            }
            $fnames[] = $v->Field;
        }
        // подготовка для INSERT
        $typeName = substr($type, 0, 6);
        if ($this->insZapazd && $typeName != 'UPDATE') {
            $type = $type . ' DELAYED';
        }
        if ($this->insIgnor && $typeName != 'REPLAC') {
            $type = $type . ' IGNORE';
        }
        if ($this->insFull) {
            $start = $type . ' INTO ' . $this->tableb . ' (`' . implode('`,`', $fnames) . '`) VALUES (';
        } else {
            $start = $type . ' INTO ' . $this->tableb . ' VALUES (';
        }
        if ($this->insExpand && $typeName != 'UPDATE') {
            $dump .= $type . ' INTO ' . $this->tableb . ' (`' . implode('`,`', $fnames) . '`) VALUES ';
        }
        $count = 0;
        $isFullDump = true;
        while ($row = $q_result->fetch()) {
            if (memory_get_usage() > $memory_limit) {
                $isFullDump = $count;
                break;
            }
            $row = array_values($row);
            // UPDATE
            if ($typeName == 'UPDATE') {
                $a = [];
                $primary = [];
                foreach ($f as $i => $v) {
                    if (isset($row[$i])) {
                        if (stristr($v->Type, 'int')) {
                            $val = $row[$i];
                        } else {
                            $val = '\'' . $pdo->query(trim($row[$i])) . '\'';
                        }
                    } else {
                        $val = 'NULL';
                    }
                    $b = $v->Field;
                    if ($this->addKav) {
                        $b = '`' . $b . '`';
                    }

                    if ($v->Key == 'PRI') {
                        $primary [] = $b . '=' . $val;
                    } else {
                        $a[] = $b . '=' . $val;
                    }
                }
                $dump .= 'UPDATE ' . $this->tableb . ' SET ' . implode(', ', $a) .
                    ' WHERE ' . implode(' AND ', $primary) . $delim;
            } elseif ($typeName == 'INSERT' || $typeName == 'REPLAC') {
                // INSERT - REPLACE
                $values = [];
                foreach ($f as $i => $v) {
                    if ($skipAi && $v->Extra != '') {
                        continue;
                    }
                    if (isset($row[$i])) {
                        if (stristr($v->Type, 'int')) {
                            $val = $row[$i];
                        } else {
                            $val = '\'' . $pdo->quote($row[$i]) . '\'';
                        }
                    } else {
                        $val = 'NULL';
                    }
                    $values [] = $val;
                }
                if ($this->insExpand) {
                    if ($count == 50) {
                        $count = 0;
                        $dump = substr($dump, 0, strlen($dump) - 3) . $delim;
                        $dump .= $start . implode(',', $values) . ')' . $delim;
                    } else {
                        $dump .= '(' . implode(',', $values) . ')' . ",\r\n";
                    }
                } else {
                    $dump .= $start . implode(',', $values) . ')' . $delim;
                }
            }
            $count++;
        }
        if ($this->insExpand) {
            $dump = substr($dump, 0, strlen($dump) - 3) . $delim;
        }
        if ($dump != null) {
            $dump = $dump . $wr;
            if ($this->comments) {
                $dump = $wr . '--' . $wr . '-- Дамп данных таблицы ' . $this->table . $wr . '--' . $wr . $wr . $dump;
            }
        }
        $this->data .= $dump;
        return $isFullDump;
    }

    /**
     * @param $type
     * @param $file
     * @return mixed|string|void|null
     */
    public function sendSQLDamp($type = 'textarea', $file = null)
    {
        if (is_null($file)) {
            $this->table != null ? $file = $this->table : $file = $this->db;
        }
        if (isAjax()) {
            if ($type == 'textarea') {
                return $this->get();
            }
            if ($type == 'zip') {
                $dir = $_SERVER['DOCUMENT_ROOT'] . '/' . MS_DIR_UPLOAD;
                if (!file_exists($dir)) {
                    mkdir($dir, 0777);
                }

                $fp = gzopen($dir . '/download.sql.gz', 'w9');
                gzwrite($fp, $this->get());
                gzclose($fp);

                return 'https://' . $_SERVER['HTTP_HOST'] . '/' . MS_DIR_UPLOAD . '/download.sql.gz';
            }
        }
        // текстовое поле
        if ($type == 'textarea') {
            return $this->get();
        // архив
        } elseif ($type == 'zip') {
            if (headers_sent()) {
                return '<h3>headers_sent...</h3>';
            }
            $attachment_name = "$file.sql.gz";
            $gzipped_data = gzencode($this->get(), 9);
            header('Content-Type: application/x-gzip'); // Or 'application/octet-stream' for a generic download
            header('Content-Disposition: attachment; filename="' . $attachment_name . '"');
            header('Content-Length: ' . strlen($gzipped_data));

            if (ob_get_level()) {
                ob_end_clean();
            }

            echo $gzipped_data;
            exit;
        }
    }

    public function getFields($table, $onlyNames = false)
    {
        global $msc;
        $a = [];
        $result = $msc->fetchPdo('SHOW FIELDS FROM ' . $table);
        if (!$result) {
            return false;
        }
        while ($row = $result->fetchObject()) {
            if ($onlyNames) {
                $a [] = $row->Field;
            } else {
                $a [] = $row;
            }
        }
        return $a;
    }
}
