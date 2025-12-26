<?php

/**
 * Преобразует значение в sql-оптимальное значение для использования в запросе (edit,add). Значение либо
 * остаётся прежним (для чисел), либо становится NULL, либо закавычивается и экранируется
 *
 * @param string Значение
 * @param string Тип поля
 * @param boolean Является ли значение NULL-пустым
 * @return string Результат
 * @package sql
 */
function processValueType($value, $type, $isNull)
{
    global $pdo;
    if ($isNull) {
        return 'NULL';
    } elseif (stripos($type, 'int') > -1 && !empty($value) && is_numeric($value)) {
        return $value;
    } else {
        return $pdo->quote($value);
    }
}

/**
 * Возвращает ключ $name массива $_GET
 *
 * @param string Ключ
 * @param string Значение по умолчанию, если ключ не будет найден
 * @return mixed Значение параметра
 * @package url
 */
function GET($name, $default = '')
{
    if (isset($_GET[$name])) {
        return $_GET[$name];
    } else {
        return $default;
    }
}

/**
 * Возвращает ключ $name массива $_POST
 *
 * @param string Ключ
 * @param string Значение по умолчанию, если ключ не будет найден
 * @return mixed Значение параметра
 * @package url
 */
function POST($name, $default = null)
{
    if (array_key_exists($name, $_POST)) {
        $res = $_POST[$name];
        if (is_numeric(ini_get('magic_quotes_gpc'))) {
            $res = stripslashesRecursive($res);
        }
        return $res;
    } else {
        return $default;
    }
}


/**
 * Аналог stripslashes(), но применяемый также рекурсивно к массивам
 *
 * @param mixed  Массив либо строка
 * @return mixed Обработанный массив либо строка
 * @package string
 */
function stripslashesRecursive($array)
{
    if (is_array($array)) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array[$k] = stripslashesRecursive($v);
            } else {
                $array[$k] = stripslashes($v);
            }
        }
    } else {
        $array = stripslashes($array);
    }
    return $array;
}

/**
 * Создание селектора <SELECT>...</SELECT> на основе массива $array, с атрибутами $attributes
 * значениями будут ключи массива, текстом - значения массива, $checked - ключ selected элемента
 *
 * @param array   Массив значений для селектора
 * @param string  Аттрибуты тега SELECT
 * @param mixed   Ключ или массив ключей в массиве, OPTION которых будет выбран selected
 * @param string  Строка пробелов - базовый отступ (для красоты кода)
 * @param boolean Надо ли устанавливать прописывать ключи в аттрибуте value="" тегов OPTION
 * @param string  Дополнительный код после первого тега <SELECT>, обычно это пустые OPTIONs
 * @return string HTML код селектора
 * @package html
 */
function plDrawSelector($array, $attributes, $checked = null, $basetab = '', $keyValue = true, $extra = null)
{
    $s = $basetab . '<select' . $attributes . '>' . "\r\n" . $extra;
    $wasSelected = false; // флаг, чтобы 1 селектед только
    foreach ($array as $k => $v) {
        $sel = null;
        if (($checked == $k || (!$keyValue && $checked == $v)) && !$wasSelected) {
            $sel = ' selected="selected"';
            $wasSelected = true;
        }
        $val = '';
        if ($keyValue) {
            $val = ' value="' . $k . '"';
        }
        $s .= $basetab . '  <option' . $val . '' . $sel . '>' . $v . '</option>' . "\r\n";
    }
    return $s .= $basetab . '</select>' . "\r\n";
}


/**
 * Получить максимальный допустимый размер аплоада файла
 *
 * @return integer Размер в байтах
 * @package file
 */
function getMaxUploadSize()
{
    if (!$filesize = ini_get('upload_max_filesize')) {
        $filesize = "5M";
    }
    $max_upload_size = get_real_size($filesize);
    if ($postsize = ini_get('post_max_size')) {
        $postsize = get_real_size($postsize);
        if ($postsize < $max_upload_size) {
            $max_upload_size = $postsize;
        }
    }
    return $max_upload_size;
}


/**
 * Считывает (и распаковывает сжатый) файл в строку
 *
 * @param string   Путь к файлу
 * @param string   MIME тип файла, иначе определяется автоматически
 * @return  mixed    string контент файла либо boolean FALSE в случае ошибок
 * @package file
 */
function readZipFile($path, $mime = '')
{
    if (!file_exists($path)) {
        return FALSE;
    }
    switch ($mime) {
        case '':
            $file = @fopen($path, 'rb');
            if (!$file) {
                return FALSE;
            }
            $test = fread($file, 3);
            fclose($file);
            if ($test[0] == chr(31) && $test[1] == chr(139)) return readZipFile($path, 'application/x-gzip');
            if ($test == 'BZh') return readZipFile($path, 'application/x-bzip');
            return readZipFile($path, 'text/plain');
        case 'zip':
            break;
        case 'text/plain':
            $file = @fopen($path, 'rb');
            if (!$file) {
                return FALSE;
            }
            $content = fread($file, filesize($path));
            fclose($file);
            break;
        case 'application/x-gzip':
            if (function_exists('gzopen')) {
                $file = @gzopen($path, 'rb');
                if (!$file) {
                    return FALSE;
                }
                $content = '';
                while (!gzeof($file)) {
                    $content .= gzgetc($file);
                }
                gzclose($file);
            } else {
                return FALSE;
            }
            break;
        case 'application/x-bzip':
            if (@function_exists('bzdecompress')) {
                $file = @fopen($path, 'rb');
                if (!$file) {
                    return FALSE;
                }
                $content = fread($file, filesize($path));
                fclose($file);
                $content = bzdecompress($content);
            } else {
                return FALSE;
            }
            break;
        default:
            return FALSE;
    }
    return $content;
}

/**
 * Возвращает реальный размер в байтах строкового php ini  представления числа
 *
 * @param string   Строковое php ini представление
 * @return integer Размер файла в байтах
 * @package number
 */
function get_real_size($size = 0)
{
    if (!$size) {
        return 0;
    }
    $scan['MB'] = 1048576;
    $scan['Mb'] = 1048576;
    $scan['M'] = 1048576;
    $scan['m'] = 1048576;
    $scan['KB'] = 1024;
    $scan['Kb'] = 1024;
    $scan['K'] = 1024;
    $scan['k'] = 1024;
    foreach (array_keys($scan) as $key) {
        if ((strlen($size) > strlen($key)) && (substr($size, strlen($size) - strlen($key)) == $key)) {
            $size = substr($size, 0, strlen($size) - strlen($key)) * $scan[$key];
            break;
        }
    }
    return $size;
}


/**
 * Преобразует размер в байтах в строковое смотрибельное представление в форме " .. Kb .. Mb"
 *
 * @param integer Размер файла в байтах
 * @return string Строковое представление
 * @package number
 */
function formatSize($bytes)
{
    if ($bytes < pow(1024, 1)) {
        return "$bytes b";
    } else if ($bytes < pow(1024, 2)) {
        return round($bytes / pow(1024, 1), 2) . ' Kb';
    } else if ($bytes < pow(1024, 3)) {
        return round($bytes / pow(1024, 2), 2) . ' Mb';
    } else if ($bytes < pow(1024, 4)) {
        return round($bytes / pow(1024, 3), 2) . ' Gb';
    }
}



/**
 * Распечатка объекта в таблицу
 *
 * @param mixed   Либо ассоциативный массив, либо mysql result
 * @param boolean Распечатать ТОЛЬКО первый элемент! Причём сделает он это вертикально!
 * @param object  Передаваемый объект Table, можно заранее задать какие-то свои значения, стили
 * @param array   Массив HTML аттрибутов к ключам массива
 * @return string HTML код таблицы
 * @package debug
 */
function MSC_printObjectTable($object, $first = false, $table = null, $attributes = array())
{
    if (!is_object($table)) {
        $table = new Table('contentTable');
        $table->setInterlace('', '#eeeeee');
    }
    $headers = array();
    $dataArray = array();
    // Преобразование входного объекта/массива
    if (!is_array($object)) {
        while ($o = $object->fetch()) {
            $dataArray [] = $o;
        }
    } else {
        $dataArray = $object;
        unset($object);
    }
    // Если первый элемент массив, печатаем его
    if (isset($dataArray[0])) {
        foreach ($dataArray as $o) {
            if ($first) {
                foreach ($o as $k => $v) {
                    if (isset($attributes[$k])) {
                        $k = '<span' . $attributes[$k] . '>' . $k . '</span>';
                    }
                    $table->makeRow($k, $v);
                }
                break;
            }
            $data = array();
            if (count($headers) == 0) {
                foreach ($o as $k => $v) {
                    $headers [] = $k;
                    $data [] = $v;
                }
                $table->makeRow($headers);
                $table->makeRow($data);
                continue;
            }
            foreach ($o as $k => $v) {
                $data [] = $v;
            }
            $table->makeRow($data);
        }
    } else {
        $table->makeRow('Параметр', 'Значение');
        foreach ($dataArray as $k => $v) {
            $table->makeRow($k, $v);
        }
    }
    return $table->make();
}

/**
 * Пишет сообщение в лог, добавляя дату/время
 *
 * @param string  Сообщение
 * @param string  SQL запрос (добавляется к сообщению)
 * @package debug
 */
function msclog($message, $sql = null)
{
    global $pdo;
    $logFile = 'data/error.log';
    if (!file_exists($logFile)) {
        $file = @fopen($logFile, 'w+');
    } else {
        $file = @fopen($logFile, 'a+');
    }
    $message = str_replace("\n", ' ', $message);
    if ($sql) {
        $sql = str_replace("\n", ' ', $sql);
    }
    $time = date('d.m.y H:i:s ');
    $string = "\n" . $time . $message;
    if ($sql != null) {
        $string .= '(' . $sql . ' ' . $pdo->errorInfo()[2] . ')';
    }
    @fwrite($file, $string);
    @fclose($file);
}

/**
 * В случае назначния set_error_handler, перехватывает сообщения об ошибках. При наличии msclog() делает лог в файл.
 * Параметры передаются автоматически обработчиком ошибок
 *
 * @package debug
 */
function mscErrorHandler($errno, $errstr, $errfile, $errline)
{
    global $mscGlobalErrorsCash, $pdo;
    $logstr = $errstr . '[' . $errfile . ':' . $errline . ']';
    if (!isset($mscGlobalErrorsCash)) {
        $mscGlobalErrorsCash = array();
    }
    if (!in_array($logstr, $mscGlobalErrorsCash)) {
        $mscGlobalErrorsCash [] = $logstr;
    } else {
        return;
    }
    if ($errno == 8) {
        return;
    }
    if (stristr($errstr, 'Unable to save result set')) {
        $logstr .= '(' . $pdo->errorInfo()[2] . ')';
    }
    $errno = str_pad($errno, 4, ' ', STR_PAD_LEFT);
    if (function_exists('msclog')) {
        msclog($errno . ' ' . $logstr);
    }
}


/**
 * (для tbl_data и tbl_compare) Обрабатывает значения полей базы данных перед выводом их в виде таблицы.
 * Обработка заключается в: для текстовых - htmlspecialchars+обрезка, для даты - отображение в поле id=tblDataInfoId
 * для нулевых значений - значение возвращается оформленным курсивом.
 *
 * @param string Значение
 * @param string Тип поля
 * @return string Обработанное значение
 * @package data view
 */
function processRowValue($v, $type)
{
    if ($v === NULL) {
        $v = MS_NULL_DESIGN;
    } else {
        // Тексты
        if (stristr($type, 'blob')) {
            $v = htmlspecialchars($v);;
        }
        if (stristr($type, 'text') || stristr($type, 'char')) {
            $v = htmlspecialchars($v);
        }
        if (strlen($v) > MS_TEXT_CUT && GET('fullText') == '') {
            $v = substr($v, 0, MS_TEXT_CUT) . ' ...';
            //$v = wordwrap($v, 20, "<br />\r\n");
        }
        // дата
        if (stristr($type, 'int') && strlen($v) == 10 && is_numeric($v)) {
            $e = ' onmouseover="get(\'tblDataInfoId\').innerHTML=\'' . date(MS_DATE_FORMAT, $v) . '\'" onmouseout="get(\'tblDataInfoId\').innerHTML=\'\'"';
            $v = '<span class="dateString"' . $e . '>' . $v . '</span>';
        }
    }
    return $v;
}


/**
 * Возвращает значение указанного параметра конфигурации
 *
 * @param string  Параметр
 * @param string  Значение по умолчанию, если параметра нет
 * @return string Значение
 * @package msc
 */
function conf($param, $default = '')
{
    global $mscConfigCash;
    if (!isset($mscConfigCash)) {
        $mscConfigCash = array();
        $data = file(MS_CONFIG_FILE);
        foreach ($data as $k => $line) {
            if (empty($line) || substr_count($line, '|') < 3) {
                continue;
            }
            list($name, $title, $value, $type) = explode('|', trim($line));
            $mscConfigCash [$name] = $value;
        }
    }
    return isset($mscConfigCash[$param]) ? $mscConfigCash[$param] : $default;
}

/**
 * Ускоренное выполнение большого кол-ва запросов с логом
 *
 * @param string База данных
 * @param string SQL запрос (передаётся по ссылке, чтобы снизить расход памяти)
 * @package msc
 */
function execSql($db, &$sql, $log = true)
{
    global $msc;
    $mysqlGenerationTime0 = round(array_sum(explode(" ", microtime())), 10);
    if (!$msc->selectDb($db)) {
        return $msc->addMessage('Не смог выбрать базу данных', null, MS_MSG_FAULT);;
    }
    if ($log) {
        $msc->logInFile($sql);
    }
    $sql = str_replace("\r\n", "\n", $sql);
    $array = explode(";\n", $sql);
    $errors = array();
    $c = 0;
    $affected = 0;
    $count = count($array);
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
        $msc->addMessage('Запрос выполнен без ошибок - ' . $info, null, MS_MSG_SUCCESS);
    } else {
        $msc->addMessage('Запрос выполнен с ошибками' . $info, null, MS_MSG_FAULT);
        $msc->addMessage(implode('<br />', $errors), null, MS_MSG_FAULT);

    }
    $mysqlGenerationTime = round(round(array_sum(explode(" ", microtime())), 10) - $mysqlGenerationTime0, 5);
    $msc->addMessage("Выполнено за $mysqlGenerationTime с.");
    $msc->addMessage("Затронуто рядов: $affected");
}

/**
 * Общая обработка для редактирования/добавления ряда
 *
 * @param integer Тип редактирования: 0-update, 1-insert
 * @package msc
 * @access private
 */
function processRowsEdit($editType)
{
    global $msc;
    if (POST('option') == 'insert') {
        $editType = 1;
    }
    $countInsert = 0;
    $fields = array_values(DatabaseTable::getFields($msc->table));
    if ($editType == 1) {
        $arrayFields = array();
        foreach ($fields as $v) {
            if (POST('option') == 'insert' && $v->Extra != null) {
                continue;
            }
            $arrayFields [] = $v->Field;
        }
    }
    $lang = array(
        array('Данные обновлены', 'Данные добавлены'),
        array('Ошибка при обновлении ряда', 'Ошибка при добавлении ряда'),
        array('Ничего не обновилось', 'Ничего не добавилось')
    );
    $rows = POST('row');
    $_POST['cond'] = POST('cond');
    foreach ($rows as $numRow => $data) {
        if ($editType == 0) {
            $where = urldecode($_POST['cond'][$numRow]);
            $cValue = $msc->fetchPdo('SELECT * FROM `' . $msc->table . '` WHERE ' . $where)->fetchObject();
        }
        $arrayValues = array();
        $countEmpty = 0;
        foreach ($data as $key => $value) {
            $default = $fields[$key]->Default;
            if ($default == $value) {
                $countEmpty++;
            }
            $type = $fields[$key]->Type;
            if ($_POST['func'][$numRow][$key] != '') {
                $value = call_user_func($_POST['func'][$numRow][$key], $value);
                $type = 'varchar';
            }
            $isNull = isset($_POST['isNull'][$numRow][$key]);
            if ($isNull) {
                $value = null;
            }
            if ($editType == 0) {
                $field = $fields[$key]->Field;
                if ($value != $cValue->$field) {
                    $arrayValues [] = '`' . $field . '`=' . processValueType($value, $type, $isNull);
                }
            } else {
                if (POST('option') == 'insert' && $fields[$key]->Extra != null) {
                    continue;
                }
                $arrayValues [] = processValueType($value, $type, $isNull);
            }
        }
        if ($countEmpty == count($data) || count($arrayValues) == 0) {
            continue;
        }
        if ($editType == 0) {
            $sql = 'UPDATE `' . $msc->table . '` SET ' . implode(', ', $arrayValues) . ' WHERE ' . $where;
        } else {
            $sql = 'INSERT INTO `' . $msc->table . '` (`' . implode('`, `', $arrayFields) . '`) VALUES (' . implode(', ', $arrayValues) . ')';
        }
        if ($msc->execPdo($sql)) {
            $msc->addMessage($lang[0][$editType], $sql, MS_MSG_SUCCESS);
            $countInsert++;
        } else {
            $msc->addMessage($lang[1][$editType], $sql, MS_MSG_FAULT, $msc->error);
        }
    }
    if ($countInsert == 0) {
        $msc->addMessage($lang[2][$editType]);
    }
}

/*
    assocTable

    echo printTable($table, [
        'htmlspecialchars' => 0,
        'class' => 'table table-condensed',
        'style' => 'width:auto; margin:0 auto',
        'headers' => 0,
        'callbackValue' => function($header, $value) {
            if ($header == 'login') {
                $value = '<a href="?page=logs&login='.$value.'">'.$value.'</a>';
            }
            return $value;
        }
    ]);
*/
function printTable($offersData, $opts = [])
{
    if (!$offersData) {
        echo '<p>Пустой массив</p>';
        return;
    }
    $hsc = isset($opts['htmlspecialchars']) ? $opts['htmlspecialchars'] : true;
    $hdr = isset($opts['headers']) ? $opts['headers'] : true;
    $attrs = '';
    if ($opts['style']) {
        $attrs = ' style="' . $opts['style'] . '"';
    }
    /*
        // Вариант шапки без бутстрапа
        echo '
        <style type="text/css">
        table.tt {empty-cells:show; border-collapse:collapse; margin:10px 0}
        table.tt td {border:1px solid #ccc; padding: 3px; vertical-align: top;}
        table.tt tr:nth-child(odd) {background-color:#eee; }
        </style>
        <table class="tt">';
    */
    $class = $opts['class'] ?: 'table table-bordered table-condensed table-sm table-hover';
    echo '
    <table class="' . $class . '" ' . $attrs . '>';
    $headers = array();
    foreach ($offersData as $vals) {
        if (is_array($vals)) {
            foreach ($vals as $k => $v) {
                $headers [$k] = $k;
            }
        }
    }
    if ($hdr) {
        echo '<tr>';
        foreach ($headers as $k => $v) {
            echo '<th>' . ($hsc ? htmlspecialchars($k) : $k) . '</th>';
        }
    }
    echo '</tr>';
    foreach ($offersData as $vals) {
        echo '<tr>';
        if (is_array($vals)) {
            foreach ($headers as $header) {
                $v = $vals[$header];
                $v = $hsc ? htmlspecialchars($v) : $v;
                if ($opts['callbackValue']) {
                    $v = call_user_func($opts['callbackValue'], $header, $v);
                }
                echo '<td>' . $v . '</td>';
            }
        } else {
            echo '<td>' . $vals . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
}


/**
 * @return bool
 */
function isajax()
{
    return POST('ajax') || GET('ajax');
}

function ajaxResult($data)
{
    header('Content-Type: application/json');
    exit(json_encode($data, JSON_INVALID_UTF8_IGNORE));
}

function ajaxError($message)
{
    ajaxResult([
        'status' => false,
        'messages' => $message
    ]);
}

function ajaxSuccess($message)
{
    ajaxResult([
        'status' => true,
        'messages' => $message
    ]);
}

function ajaxResultWithMessages()
{
    global $msc;
    $data = $msc->getMessagesData();
    foreach ($data as $item) {
        if ($item['type'] == MS_MSG_ERROR || $item['type'] == MS_MSG_FAULT) {
            ajaxError($data);
        }
    }
    ajaxSuccess($data);
}

function exitError($message)
{
    if (isajax()) {
        global $msc;
        $msc->addMessage($message, null, MS_MSG_FAULT);
        ajaxResultWithMessages();
    } else {
        exit($message);
    }
}