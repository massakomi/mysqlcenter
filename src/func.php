<?php

use enum\MessageType;

/**
 * Возвращает ключ $name массива $_GET
 *
 * @param string $name Ключ
 * @param string|null $default Значение по умолчанию, если ключ не будет найден
 * @return mixed Значение параметра
 */
function GET(string $name, ?string $default = ''): mixed
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
 * @param string $name Ключ
 * @param string|null $default Значение по умолчанию, если ключ не будет найден
 * @return mixed Значение параметра
 */
function POST(string $name, ?string $default = null): mixed
{
    if (array_key_exists($name, $_POST)) {
        return $_POST[$name];
    } else {
        return $default;
    }
}

/**
 * Логи всех sql запросов в базы
 *
 * @param $string
 * @param $db
 * @return void
 */
function logInFile($string, $db): void
{
    if (config('sqlLog') != '1' || !$db) {
        return;
    }
    $string .= ";\r\n";
    writeLogFile($string, $db . '.sql');
}

/**
 * Логи ошибок + дата/время
 *
 * @param string $message Сообщение
 * @param string|null $sql SQL запрос (добавляется к сообщению)
 */
function logError(string $message, ?string $sql = null): void
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
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 * @return void
 */
function errorHandlerNotice($errno, $errstr, $errfile, $errline): void
{
    global $msc;
    $log = $errstr . ' [' . $errfile . ':' . $errline . ']';
    if (isset($msc)) {
        $msc->error($log);
    } else {
        throw new ErrorException($errstr, $errno);
    }
}

/**
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 * @return void
 */
function errorHandlerFile($errno, $errstr, $errfile, $errline): void
{
    global $mscGlobalErrorsCash, $pdo;
    $log = $errstr . '[' . $errfile . ':' . $errline . ']';
    if (!isset($mscGlobalErrorsCash)) {
        $mscGlobalErrorsCash = [];
    }
    if (!in_array($log, $mscGlobalErrorsCash)) {
        $mscGlobalErrorsCash [] = $log;
    } else {
        return;
    }
    if ($errno == 8) {
        return;
    }
    if (stristr($errstr, 'Unable to save result set')) {
        $log .= '(' . $pdo->errorInfo()[2] . ')';
    }
    $errno = str_pad($errno, 4, ' ', STR_PAD_LEFT);
    logError($errno . ' ' . $log);
}

/**
 * Возвращает значение указанного параметра конфигурации
 *
 * @param string $param Параметр
 * @param string $default Значение по умолчанию, если параметра нет
 * @return string Значение
 */
function config(string $param, string $default = ''): string
{
    global $mscConfigCash;
    if (!isset($mscConfigCash)) {
        $mscConfigCash = [];
        $json = json_decode(file_get_contents(MS_CONFIG_FILE));
        foreach ($json as $item) {
            $value = $item->value;
            if ($item->type === 'integer' || $item->type === 'boolean') {
                $value = (int)$value;
            }
            $mscConfigCash [$item->name] = $value;
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
 * @return never
 */
function ajaxResult($data): never
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
        if ($item->type == MessageType::Error->value) {
            ajaxError($data);
        }
    }
    ajaxResult([
        'status' => true,
        'messages' => $data
    ]);
}
