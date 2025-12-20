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

const MS_CONFIG_FILE = 'includes/config.txt';
const MS_CHARACTER_SET = 'utf8';
const MS_COLLATION = 'utf8_general_ci';

// чтобы убрать функции в func.php и при этом начать лог ошибок раньше
require_once DIR_MYSQL . 'includes/func.php';
if (conf('errorlog') == '1') {
    set_error_handler('mscErrorHandler');
}

$msc = new MSCenter(); // чтобы начать анализ скорости раньше

/**
 *    Конфигурация
 */

// 1. загрузка локального конфига
if (!file_exists(DIR_MYSQL . 'config_local.php')) {
    exitError('File "config_local.php" was not founded<br />
    You need to create this file with db config parameters LIKE this: <br /> <br />
    define("DB_HOST",       "localhost"); <br />
    define("DB_USERNAME",   "user_name"); <br />
    define("DB_PASSWORD",   "");
');
}
include DIR_MYSQL . 'config_local.php';

if (isset($_GET['s']) && $_GET['s'] == 'logout') {
    setcookie('msc_pass', '', time(), '/');
    setcookie('msc_user', '', time(), '/');
    unset($_SESSION['msc_user'], $_SESSION['msc_pass']);
    header('Location: ' . $_SERVER['PHP_SELF']);
}

// 2. проверка пользователя на знание логина и пароля к базе
// На удаленном-ремоте сервере проверяем, чтобы входили только под конфигурац. данными
if (!defined('MSC_LOCAL_USE')) {
    $enterErrors = array();
    if (isset($_POST['pass']) && isset($_POST['user'])) {
        if ($_POST['user'] != DB_USERNAME) {
            $enterErrors [] = 'Username is not equal config param DB_USERNAME';
        }
        if ($_POST['pass'] != DB_PASSWORD) {
            $enterErrors [] = 'Password is not equal config param DB_PASSWORD';
        }
    }
    // не вошли
    if (count($enterErrors) != 0) {
        $errorMessage = '<strong>You are not entered</strong> <br />';
        $errorMessage .= implode('<br />', $enterErrors);
        //$pagel->loginPage($errorMessage);
        exit;
    }
}

if (array_key_exists('cookies', $_POST)) {
    $_COOKIE = $_POST['cookies'];
}

global $msc, $umaker, $pagel, $pdo;

$msc->init();

// 3. проверка соединения с базой
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.$msc->getCurrentDatabase(), DB_USERNAME, DB_PASSWORD, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8', collation_connection=".MS_COLLATION.', character_set_server='.MS_CHARACTER_SET.', sql_mode=""'
    ]);
} catch (PDOException $e) {
    $msc->clearCurrentDatabase();
    exitError('Unable to pdo-connect to database on "' . DB_HOST . '" as ' . DB_USERNAME . '<br />' . $e->getMessage());
}

// Если вошли нормально записываем значения в куки
if (isset($_POST['pass']) && isset($_POST['user'])) {
    // В куки пишем только если заходят с конфигурационных данных, и куки еще не записаны
    if (DB_PASSWORD == $_POST['pass'] && md5(DB_PASSWORD) != @$_COOKIE['msc_pass']) {
        setcookie('msc_user', $_POST['user'], time() + 14 * 3600 * 24, '/');
        setcookie('msc_pass', md5($_POST['pass']), time() + 14 * 3600 * 24, '/');
        $_COOKIE ['msc_user'] = $_POST['user'];
        $_COOKIE ['msc_pass'] = md5($_POST['pass']);
    }
    $_SESSION['msc_user'] = $_POST['user'];
    $_SESSION['msc_pass'] = $_POST['pass'];
}

// Выбираем базу и делаем первые запросы только после определения базы
$msc->selectDb($msc->getCurrentDatabase());

// DEFINES

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

list($vi, $vs) = getServerVersion();
define('PMA_MYSQL_INT_VERSION', $vi);
define('PMA_MYSQL_STR_VERSION', $vs);
define('MAX_UPLOAD_SIZE', getMaxUploadSize());