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
 * @param string $string
 * @param string $db
 * @return void
 */
function logInFile(string $string, string $db): void
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
 * @param string $string
 * @param string $filename
 * @return void
 */
function writeLogFile(string $string, string $filename): void
{
    if (!file_exists(MS_DIR_LOGS)) {
        if (!mkdir(MS_DIR_LOGS)) {
            return;
        }
    }
    $logFile = MS_DIR_LOGS . '/' . $filename;
    $file = fopen($logFile, file_exists($logFile) ? 'a+' : 'w+');
    if (!$file) {
        return;
    }
    fwrite($file, $string);
    fclose($file);
}


/**
 * If the function returns false then the normal error handler continues.
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 * @return bool
 */
function errorHandlerNotice(int $errno, string $errstr, string $errfile, int $errline): bool
{
    global $msc;
    $log = $errstr . ' [' . $errfile . ':' . $errline . ']';
    if (isset($msc)) {
        $msc->error($log);
        return true;
    }
    return false;
}

/**
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 * @return bool
 */
function errorHandlerFile(int $errno, string $errstr, string $errfile, int $errline): bool
{
    global $mscGlobalErrorsCash, $pdo;
    $log = $errstr . '[' . $errfile . ':' . $errline . ']';
    if (!isset($mscGlobalErrorsCash)) {
        $mscGlobalErrorsCash = [];
    }
    if (!in_array($log, $mscGlobalErrorsCash)) {
        $mscGlobalErrorsCash [] = $log;
    } else {
        return false;
    }
    if ($errno == 8) {
        return false;
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
 * @param array $data
 * @return never
 */
function ajaxResult(array $data): never
{
    header('Content-Type: application/json');
    exit(json_encode($data, JSON_INVALID_UTF8_IGNORE));
}

/**
 * @param array $messages
 * @return void
 */
function ajaxError(array $messages): void
{
    ajaxResult([
        'status' => false,
        'messages' => $messages
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
