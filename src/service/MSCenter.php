<?php

declare(strict_types=1);

namespace service;

use database\{Driver, MySQL, PostgreSQL, Query};
use dto\ConnectConfig;
use dto\Message;
use enum\MessageType;

/**
 * Управляющий класс
 * Он отвечает за следующие действия:
 * - сообщения
 * - заголовок раздела h1 и страницы title
 * - текущая страница, БД, таблица
 */
class MSCenter extends Query
{
    public string $db = '';
    public string $table = '';
    public string $page = '';
    public string $host = '';
    public string $user = '';
    public ?Driver $driver = null;
    public string $driverName = '';
    public string $pageTitle = ''; // для заголовка раздела h1

    /* @var Message[] */
    public array $messages = [];

    /**
     * Время timestamp начала работы программы. Используется для подсчёта времени выполнения.
     */
    public float $timer;

    public bool $allowRepeatMessages = false;

    /**
     * Конструктор, для начала анализа скорости
     * @access private
     */
    public function __construct()
    {
        $this->timer = round(array_sum(explode(" ", microtime())), 10);
    }

    /**
     * Инициализация - отдельно от конструтора, чтобы тот раньше запустился
     * @access private
     */
    public function init()
    {
        $this->initCurrentDatabase();
        if ($this->table == null && $this->db != null) {
            $this->table = GET('table');
        }
        $this->initCurrentPage();
    }

    /**
     * Возвращает заголовок страницы, вызывается только в основном шаблоне
     * @access private
     */
    public function getPageTitle()
    {
        if ($this->pageTitle == null) {
            $this->pageTitle = $this->getWindowTitle();
        }
        return $this->pageTitle;
    }

    /**
     * Возвращает заголовок окна, относится только к основному шаблону
     * @access private
     */
    public function getWindowTitle()
    {
        $mainTitle = null;
        $mainTitle .= $this->table != null ? "$this->table < " : null;
        $mainTitle .= $this->db != null ? $this->db : $this->page;
        return $mainTitle;
    }

    /**
     * Возвращает текущую отображаемую базу данных (которую мы видим), вызывается при инициализации
     * @access private
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
        $settings = json_decode(file_get_contents(MS_CONNECT_CONFIG_FILE), true);
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
            if ($config->database && $config->database != $this->db) {
                if (preg_match('~database .*? does not exist~', $e->getMessage())) {
                    $this->clearCurrentDatabase();
                    $this->connect();
                    return;
                }
            }
            $msg = 'Unable to pdo-connect to database on "' . $config->host . '" as ' . $config->user . '<br />';
            $this->connectError($msg . $e->getMessage());
        }
    }

    /**
     * Приходится тут все сбрасывать, т.к. к этому времени в init можно все заполнится
     */
    public function connectError($msg): void
    {
        $this->error($msg, '');
        $this->page = 'login';
        $this->db = '';
        $this->table = '';
    }

    /**
     * Возвращает алиас текущего раздела, вызывается при инициализации
     * @access private
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
     * @return array
     */
    public function getMessagesData(): array
    {
        return $this->messages;
    }


    /**
     * Ошибка
     *
     * @param string $text
     * @param null $sql
     * @return bool
     */
    public function error(string $text, $sql = null): bool
    {
        return $this->addMessage($text, MessageType::Error, $sql);
    }

    /**
     * Успешная операция
     *
     * @param string $text
     * @param null $sql
     * @return bool
     */
    public function success(string $text, $sql = null): bool
    {
        return $this->addMessage($text, MessageType::Success, $sql);
    }

    /**
     * Ошибка, но не вызывает status error при ajax запросах
     *
     * @param string $text
     * @param null $sql
     * @return bool
     */
    public function notice(string $text, $sql = null): bool
    {
        return $this->addMessage($text, MessageType::Notice, $sql);
    }

    /**
     * Сохраняет важное сообщение о процессе выполнения, которое будет выведено пользователю
     *
     * @param string $text текст сообщения
     * @param MessageType $type сообщения MS_MSG_[SIMPLE SUCCESS FAULT ERROR NOTICE]
     * @param string|null $sql sql запрос
     * @return bool
     */
    private function addMessage(string $text, MessageType $type, ?string $sql): bool
    {
        if (!$this->allowRepeatMessages) {
            foreach ($this->messages as $message) {
                if ($message->text == $text && $message->sql == $sql) {
                    return true;
                }
            }
        }
        $this->messages [] = new Message(
            text: $text,
            type: $type->value,
            color: $type->getColor(),
            error: $this->error,
            sql: $sql,
            rows: $this->affectedRows,
        );
        if ($type == MessageType::Error) {
            return false;
        }
        return true;
    }
}
