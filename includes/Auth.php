<?php

/**
 *
 */
class Auth
{
    public function __construct()
    {
        $this->logout();
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
