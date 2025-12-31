<?php

use service\MSCenter;
use service\Utils;

spl_autoload_register(function ($class) {
    $path = [
        DIR_MYSQL . 'src/' . $class . '.php',
        DIR_MYSQL . 'controller/' . $class . '.php',
        DIR_MYSQL . $class . '.php',
    ];
    foreach ($path as $value) {
        if (file_exists($value)) {
            include_once $value;
            return true;
        }
    }
    //throw new Exception("Autoload error $class");
});

// CORE
error_reporting(E_ALL);
session_start();
ini_set('display_errors', '1');
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 1800);
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Europe/Moscow');
}

require_once DIR_MYSQL . 'src/func.php';

// Все константы
const MS_URL = '';
const MS_DIR_IMG = 'images/';
const MS_DIR_CSS = 'css/';
const MS_DIR_UPLOAD = DIR_MYSQL . 'logs';
const MS_DIR_LOGS = DIR_MYSQL . 'logs';

const MS_CONNECT_CONFIG_FILE = DIR_MYSQL . 'config/connect.txt';
const MS_CONFIG_FILE = DIR_MYSQL . 'config/config.txt';
const MS_CONFIG_DEFAULT_FILE = DIR_MYSQL . 'config/config_default.txt';
const MS_POPULAR_TABLES_FILE = DIR_MYSQL . 'config/popular.json';
const MS_CHARACTER_SET = 'utf8';
const MS_COLLATION = 'utf8_general_ci';

define('MS_DEFAULT_PART', config('rpage'));
define('MS_LIST_LINKS_RANGE', config('linksrange'));
define('MS_HEAD_WRAP', config('headwrap'));
define('MS_TEXT_CUT', config('textcut'));
define('MS_ROWS_INSERT', config('insertrows'));
define('MS_DATE_FORMAT', config('datetimeformat'));
define('MSC_MAX_DB_LIST', 100);  // вряд ли такое будет
define('MS_FIELDS_COUNT', config('fieldsmax'));
define('MS_NULL_DESIGN', config('nulldesign'));
define('MAX_UPLOAD_SIZE', Utils::getMaxUploadSize());

if (config('errorlog') == '1') {
    set_error_handler('mscErrorHandler');
}

global $msc, $pdo;

$msc = new MSCenter(); // чтобы начать анализ скорости раньше

$msc->init();

$msc->connect();

// Выбираем базу и делаем первые запросы только после определения базы
;if (!$msc->selectDb($msc->db)) {
    $msc->clearCurrentDatabase();
}
