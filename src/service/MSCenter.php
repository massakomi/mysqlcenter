<?php

declare(strict_types=1);

namespace service;

use database\{Driver, MySQL, PostgreSQL, Query, Server};
use dto\ConnectConfig;
use dto\Message;

/**
 * Управляющий класс
 * Он отвечает за следующие действия:
 * - сообщения
 * - заголовок раздела h1 и страницы title
 * - текущая страница, БД, таблица
 */
class MSCenter extends Query
{
    use \service\Message;

    public string $db = '';
    public string $table = '';
    public string $page = '';
    public string $host = '';
    public string $user = '';

    public ?Driver $driver = null;
    public string $driverName = '';

    public string $pageTitle = '';
    public float $timer;

    /**
     * Конструктор, для начала анализа скорости
     */
    public function __construct()
    {
        $this->timer = round(array_sum(explode(" ", microtime())), 10);
    }

    /**
     * Инициализация - отдельно от конструктора, чтобы тот раньше запустился
     */
    public function init(): void
    {
        $this->initCurrentDatabase();
        if ($this->table == null && $this->db != null) {
            $this->table = GET('table');
        }
        $this->initCurrentPage();
    }

    /**
     * Возвращает заголовок страницы, вызывается только в основном шаблоне
     * @return string
     */
    public function getPageTitle(): string
    {
        if ($this->pageTitle == null) {
            $this->pageTitle = $this->getWindowTitle();
        }
        return $this->pageTitle;
    }

    /**
     * Возвращает заголовок окна, относится только к основному шаблону
     */
    public function getWindowTitle(): string
    {
        $mainTitle = null;
        $mainTitle .= $this->table != null ? "$this->table < " : null;
        $mainTitle .= $this->db != null ? $this->db : $this->page;
        return $mainTitle;
    }

    /**
     * Возвращает алиас текущего раздела, вызывается при инициализации
     */
    private function initCurrentPage(): void
    {
        if (!$this->connectConfigExists()) {
            $this->page = 'login';
            return;
        }
        $defaultPage = $this->db ? 'tbl_list' : 'db_list';
        if ($this->page == null) {
            if (count($_GET) > 0) {
                if (GET('s') != '') {
                    $this->page = GET('s');
                } else {
                    $a = array_key_first($_GET);
                    $value = $_GET[$a];
                    if ($value != '') {
                        $this->page = $defaultPage;
                    } else {
                        $this->page = $a;
                    }
                }
            } else {
                $this->page = $defaultPage;
            }
        }
    }

    /**
     * Возвращает текущую отображаемую базу данных (которую мы видим), вызывается при инициализации
     */
    private function initCurrentDatabase(): void
    {
        if (!$this->connectConfigExists()) {
            return;
        }
        $db = GET('db') ?: POST('db');
        if ($db != '') {
            if ($this->db != $db) {
                setcookie('mc_db', $db, time() + 3600 * 24 * 14, '/');
            }
            $this->db = $db;
        } elseif (!empty($_SESSION['db'])) {
            $this->db = $_SESSION['db'];
        } elseif (!empty($_COOKIE['mc_db'])) {
            $this->db = $_COOKIE['mc_db'];
        }
    }

    public function clearCurrentDatabase(): void
    {
        unset($_COOKIE['mc_db']);
        setcookie('mc_db', '', -1, '/');
        $_SESSION['db'] = '';
        $this->db = '';
        if ($this->page !== 'login') {
            $this->page = 'db_list';
        }
    }

    /**
     * @return bool
     */
    public function connected(): bool
    {
        return $this->host !== '';
    }

    /**
     * @return bool
     */
    public function connectConfigExists(): bool
    {
        return file_exists(MS_CONNECT_CONFIG_FILE);
    }

    /**
     *
     */
    public function getConfig(): ConnectConfig
    {
        $json = file_get_contents(MS_CONNECT_CONFIG_FILE) ?: '';
        $settings = json_decode($json, true);
        $current = $settings['current'];
        $config = $settings['config'][$current];
        if (!$config) {
            $this->connectError("Конфиг существует, но настройка current($current) в нем не найдена");
            return new ConnectConfig();
        }
        return new ConnectConfig(
            $config['host'],
            $config['port'],
            $config['database'],
            $config['user'],
            $config['password'],
            $config['driver'],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        if (!$this->connectConfigExists()) {
            return;
        }
        $config = $this->getConfig();
        try {
            $this->connectPdo($config, $this->db);
            $this->host  = $config->host;
            $this->user  = $config->user;
            $this->driverName  = $config->driver;
            $this->driver  = $config->driver == 'pgsql' ? new PostgreSQL() : new MySQL();
            if (!$this->db) {
                $this->db  = $config->database;
            }
        } catch (\PDOException $e) {
            // Если подставленная база не сработала - берем базу из конфигурации
            if (preg_match('~(database .*? does not exist|Unknown database)~', $e->getMessage())) {
                if ($config->database) {
                    if ($config->database != $this->db) {
                        $this->notice("Ошибка подключения к базе $this->db, подключаемся к $config->database");
                        $this->clearCurrentDatabase();
                        $this->connect();
                        return;
                    }
                } else {
                    $this->notice("Ошибка подключения к базе $this->db, очищаю сохраненную базу");
                    $this->clearCurrentDatabase();
                    return;
                }
            }
            $msg = 'Unable to pdo-connect to database on "' . $config->host . '" as ' . $config->user . '<br />';
            $this->connectError($msg . $e->getMessage());
        }
    }

    /**
     * Приходится тут все сбрасывать, т.к. к этому времени в init можно все заполнится
     * @param string $msg
     */
    public function connectError(string $msg): void
    {
        $this->error($msg, '');
        $this->page = 'login';
        $this->db = '';
        $this->table = '';
    }
}
