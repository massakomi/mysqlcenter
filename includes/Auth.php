<?php

/**
 *
 */
class Auth
{
    public function __construct()
    {
        $useLocal = 1;

        if ($useLocal) {
            $this->useLocalFile();
        } else {

        }

        $this->logout();

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
    }

    /**
     * {@inheritdoc}
     */
    public function useLocalFile()
    {
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
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        if (isset($_GET['s']) && $_GET['s'] == 'logout') {
            setcookie('msc_pass', '', time(), '/');
            setcookie('msc_user', '', time(), '/');
            unset($_SESSION['msc_user'], $_SESSION['msc_pass']);
            header('Location: ' . $_SERVER['PHP_SELF']);
        }
    }

    public function afterConnect(): void
    {
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
    }
}

