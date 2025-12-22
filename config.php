<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

spl_autoload_register(function ($class) {
    include_once 'includes/' . $class . '.php';
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
$auth = new Auth();

global $msc, $umaker, $pagel, $pdo;

$msc->init();

// Настройки
define('MS_APP_NAME', 'MySQL React');
define('MS_APP_VERSION', substr('$Revision: 1.124 $', 10, 6));
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
define('MAX_UPLOAD_SIZE', getMaxUploadSize());

if (!$msc->connected()) {
    return;
}

// 3. проверка соединения с базой
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.$msc->getCurrentDatabase(), DB_USERNAME, DB_PASSWORD, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8', collation_connection=".MS_COLLATION.', character_set_server='.MS_CHARACTER_SET.', sql_mode=""'
    ]);
} catch (PDOException $e) {
    $msc->clearCurrentDatabase();
    exitError('Unable to pdo-connect to database on "' . DB_HOST . '" as ' . DB_USERNAME . '<br />' . $e->getMessage());
}

$auth->afterConnect();

// Выбираем базу и делаем первые запросы только после определения базы
$msc->selectDb($msc->getCurrentDatabase());