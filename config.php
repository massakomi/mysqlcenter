<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

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
define('MS_URL', '');
define('MS_DIR_TPL', 'tpl/');
define('MS_DIR_IMG', 'tpl/images/');
define('MS_DIR_JS', 'js/');
define('MS_DIR_CSS', 'tpl/');

define('MS_CONFIG_FILE', 'includes/config.txt');
define('MS_CHARACTER_SET', 'utf8');
define('MS_COLLATION', 'utf8_general_ci');

// чтобы убрать функции в func.php и при этом начать лог ошибок раньше
require_once DIR_MYSQL . 'includes/func.php';
if (conf('errorlog') == '1') {
    set_error_handler('mscErrorHandler');
}

require_once DIR_MYSQL . 'includes/MSCenter.php';
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
    define("DB_PASSWORD",   ""); <br />
    define("DB_NAME",       "db_name");
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
        $pagel->loginPage($errorMessage);
        exit;
    }
}

if (array_key_exists('cookies', $_POST)) {
    $_COOKIE = $_POST['cookies'];
}

/*
// Если есть данные формы, берем оттуда
if (isset($_POST['pass']) && isset($_POST['user'])) {
    define('DB_USERNAME_CUR', $_POST['user']);
    define('DB_PASSWORD_CUR', $_POST['pass']);
    $enterType = 'POST';

// Если есть данные сессии, то оттуда
} elseif (isset($_SESSION['msc_pass']) && isset($_SESSION['msc_user'])) {
    define('DB_USERNAME_CUR', $_SESSION['msc_user']);
    define('DB_PASSWORD_CUR', $_SESSION['msc_pass']);
    $enterType = 'SESSION';

// Если нет ПОСТа и Сессии, пробуем Куки. Но оттуда берем только если это конфиг данные
// (потому что мы не можем в куки хранить пароль без md5, а md5 пароль мы не можем использовать при коннекте)
} elseif (isset($_COOKIE['msc_pass']) && isset($_COOKIE['msc_user'])
    && md5(DB_PASSWORD) == $_COOKIE['msc_pass']) {
    define('DB_USERNAME_CUR', $_COOKIE['msc_user']);
    define('DB_PASSWORD_CUR', DB_PASSWORD);
    $enterType = 'COOKIE';
} else {
    $pagel->loginPage();
    exit;
}*/

global $msc, $umaker, $pagel, $connection;
/*
if ($pagel) {
    $pagel->enterType = $enterType;
}*/

// 3. проверка соединения с базой
/*try {
    $connection = mysqli_connect(DB_HOST, DB_USERNAME_CUR, DB_PASSWORD_CUR);
} catch (\Exception $e) {*/
    try {
        $connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);
    } catch (\Exception $e) {
        exitError('Unable to connect to database on "' . DB_HOST . '" as ' . DB_USERNAME . '<br />' . $e->getMessage());
        //$pagel->loginPage();
    }
//}
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

require_once DIR_MYSQL . 'includes/Server.php';
$msc->init();
// Важные
require_once DIR_MYSQL . 'includes/ActionProcessor.php';
require_once DIR_MYSQL . 'includes/UrlMaker.php';

// Engine классы
require_once DIR_MYSQL . 'includes/table.class.php';

// Для кэширования таблиц
require_once dirname(__FILE__) . '/includes/DatabaseTable.php';

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
define('MS_DB_FULL_INFO', conf('dbfullinfo'));
define('MS_DATE_FORMAT', conf('datetimeformat'));
define('MSC_MAX_DB_LIST', 100);  // вряд ли такое будет
define('MS_FIELDS_COUNT', conf('fieldsmax'));
define('MS_NULL_DESIGN', conf('nulldesign'));

list($vi, $vs) = getServerVersion();
define('PMA_MYSQL_INT_VERSION', $vi);
define('PMA_MYSQL_STR_VERSION', $vs);
define('MAX_UPLOAD_SIZE', getMaxUploadSize());

// Конфигурация PMA
$byteUnits = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
$day_of_week = array('Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб');
$month = array('Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек');
