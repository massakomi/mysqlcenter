<?php

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
        return $res;
    } else {
        return $default;
    }
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
    $logFile = MS_DIR_LOGS . '/error.log';
    if (!file_exists($logFile)) {
        $file = fopen($logFile, 'w+');
    } else {
        $file = fopen($logFile, 'a+');
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
 * Возвращает значение указанного параметра конфигурации
 *
 * @param string $param Параметр
 * @param string $default Значение по умолчанию, если параметра нет
 * @return string Значение
 * @package msc
 */
function config($param, $default = ''): string
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
    return $mscConfigCash[$param] ?? $default;
}

/**
 * @return bool
 */
function isajax(): bool
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

/**
 * @param $message
 * @return void
 */
function ajaxSuccess($message): void
{
    ajaxResult([
        'status' => true,
        'messages' => $message
    ]);
}

function ajaxResultWithMessages(): void
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

/**
 * @param $message
 * @return void
 */
function exitError($message): void
{
    if (isajax()) {
        global $msc;
        $msc->addMessage($message, null, MS_MSG_FAULT);
        ajaxResultWithMessages();
    } else {
        exit($message);
    }
}
