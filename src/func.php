<?php

use enum\MessageType;

/**
 * Возвращает ключ $name массива $_GET
 *
 * @param string Ключ
 * @param string Значение по умолчанию, если ключ не будет найден
 * @return mixed Значение параметра
 */
function GET($name, $default = ''): mixed
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
function POST($name, $default = null): mixed
{
    if (array_key_exists($name, $_POST)) {
        $res = $_POST[$name];
        return $res;
    } else {
        return $default;
    }
}

/**
 * Логи всех sql запросов в базы
 *
 * @access private
 * @param $string
 * @param $db
 * @return bool|void
 */
function logInFile($string, $db)
{
    if (config('sqllog') != '1' || !$db) {
        return;
    }
    $string .= ";\r\n";
    writeLogFile($string, $db . '.sql');
}

/**
 * Логи ошибок + дата/время
 *
 * @param string  Сообщение
 * @param string  SQL запрос (добавляется к сообщению)
 * @package debug
 */
function logError($message, $sql = null): void
{
    global $pdo;
    $message = str_replace("\n", ' ', $message);
    if ($sql) {
        $sql = str_replace("\n", ' ', $sql);
    }
    $time = date('d.m.y H:i:s ');
    $string = "\n" . $time . $message;
    if ($sql != null) {
        $string .= '(' . $sql . ' ' . $pdo->errorInfo()[2] . ')';
    }
    writeLogFile($string, 'error.log');
}

/**
 * Общий метод записи в файл
 * @param $string
 * @param $filename
 * @return false|void
 */
function writeLogFile($string, $filename)
{
    if (!file_exists(MS_DIR_LOGS)) {
        if (!mkdir(MS_DIR_LOGS)) {
            return;
        }
    }
    $logFile = MS_DIR_LOGS . '/' . $filename;
    $file = fopen($logFile, file_exists($logFile) ? 'a+' : 'w+');
    if (!$file) {
        return false;
    }
    fwrite($file, $string);
    fclose($file);
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
        $mscGlobalErrorsCash = [];
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
    logError($errno . ' ' . $logstr);
}

/**
 * Возвращает значение указанного параметра конфигурации
 *
 * @param string $param Параметр
 * @param string $default Значение по умолчанию, если параметра нет
 * @return string Значение
 * @package msc
 */
function config(string $param, string $default = ''): string
{
    global $mscConfigCash;
    if (!isset($mscConfigCash)) {
        $mscConfigCash = [];
        $data = file(MS_CONFIG_FILE);
        foreach ($data as $k => $line) {
            if (empty($line) || substr_count($line, '|') < 3) {
                continue;
            }
            list($name, $title, $value, $type) = explode('|', trim($line));
            $mscConfigCash [$name] = $value;
        }
    }
    return $mscConfigCash[$param] ?? $default;
}

/**
 * @return bool
 */
function isAjax(): bool
{
    return POST('ajax') || GET('ajax');
}

/**
 * @param $data
 * @return void
 */
function ajaxResult($data): void
{
    header('Content-Type: application/json');
    exit(json_encode($data, JSON_INVALID_UTF8_IGNORE));
}

/**
 * @param $message
 * @return void
 */
function ajaxError($message): void
{
    ajaxResult([
        'status' => false,
        'messages' => $message
    ]);
}

function ajaxResultWithMessages(): void
{
    global $msc;
    $data = $msc->getMessagesData();
    foreach ($data as $item) {
        if ($item['type'] == MessageType::Error) {
            ajaxError($data);
        }
    }
    ajaxResult([
        'status' => true,
        'messages' => $data
    ]);
}
