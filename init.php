<?php

spl_autoload_register(function ($class) {
    $path = [
        'includes/' . $class . '.php',
        $class . '.php',
    ];
    foreach ($path as $value) {
        if (file_exists($value)) {
            include_once $value;
            break;
        }
    }
});

// CORE
error_reporting(E_ALL);
session_start();
ini_set('display_errors', '1');
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 1800);
//set_magic_quotes_runtime(0);
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Europe/Moscow');
}

// Папки
const MS_URL = '';
const MS_DIR_TPL = 'tpl/';
const MS_DIR_IMG = 'tpl/images/';
const MS_DIR_JS = 'js/';
const MS_DIR_CSS = 'tpl/';

const MS_CONNECT_CONFIG_FILE = 'docs/connect.txt';
const MS_CONFIG_FILE = 'docs/config.txt';
const MS_CHARACTER_SET = 'utf8';
const MS_COLLATION = 'utf8_general_ci';

// чтобы убрать функции в func.php и при этом начать лог ошибок раньше
require_once DIR_MYSQL . 'includes/func.php';
if (conf('errorlog') == '1') {
    set_error_handler('mscErrorHandler');
}

$msc = new MSCenter(); // чтобы начать анализ скорости раньше
$umaker = new UrlMaker();

global $msc, $umaker, $pagel, $pdo;

$msc->init();

// Настройки 2
define('MS_DEFAULT_PART', conf('rpage'));
define('MS_LIST_LINKS_RANGE', conf('linksrange'));
define('MS_HEAD_WRAP', conf('headwrap'));
define('MS_TEXT_CUT', conf('textcut'));
define('MS_ROWS_INSERT', conf('insertrows'));
define('MS_DATE_FORMAT', conf('datetimeformat'));
define('MSC_MAX_DB_LIST', 100);  // вряд ли такое будет
define('MS_FIELDS_COUNT', conf('fieldsmax'));
define('MS_NULL_DESIGN', conf('nulldesign'));
define('MAX_UPLOAD_SIZE', Utils::getMaxUploadSize());

if ($msc->connectConfigExists()) {
    $msc->connect();
}

// Выбираем базу и делаем первые запросы только после определения базы
;if (!$msc->selectDb($msc->db)) {
    $msc->clearCurrentDatabase();
}
