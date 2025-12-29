<?php

use database\Driver;
use database\MySQL;
use database\PostgreSQL;

// типы сообщений
const MS_MSG_SIMPLE = 1; // инфо
const MS_MSG_SUCCESS = 2; // успешная операция
const MS_MSG_FAULT = 3; // операция не удалась
const MS_MSG_ERROR = 4; // серъёзная ошибка
const MS_MSG_NOTICE = 5; // непонятная ситуация, замечание

/**
 * Управляющий класс
 * Он отвечает за следующие действия:
 * - сообщения
 * - заголовок раздела h1 и страницы title
 * - текущая страница, БД, таблица
 */
class MSCenter extends DatabaseQuery
{
    public string $db = '';
    public string $table = '';
    public string $page = '';
    public string $host = '';
    public string $user = '';
    public ?Driver $driver = null;
    public string $driverName = '';
    public string $pageTitle = ''; // для заголовка раздела h1

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
    private function initCurrentDatabase()
    {
        if (!$this->connectConfigExists()) {
            return '';
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
     * {@inheritdoc}
     */
    public function connect(): void
    {
        global $pdo;
        if (!$this->connectConfigExists()) {
            return;
        }
        $settings = json_decode(file_get_contents(MS_CONNECT_CONFIG_FILE), true);
        $current = $settings['current'];
        $config = $settings['config'][$current];
        if (!$config) {
            $this->connectError("Конфиг существует, но настройка current($current) в нем не найдена");
            return;
        }
        extract($config);
        try {
            $options = [
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8', collation_connection=" . MS_COLLATION .
                    ', character_set_server=' . MS_CHARACTER_SET . ', sql_mode=""'
            ];
            $pdo = new PDO($driver.':host=' . $host . ';port='.$port.';dbname=' . $database, $user, $password, $options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->host  = $host;
            $this->user  = $user;
            $this->driverName  = $driver;
            $this->driver  = $driver == 'pgsql' ? new PostgreSQL() : new MySQL();
            $this->db  = $database;
        } catch (PDOException $e) {
            $this->clearCurrentDatabase();
            $msg = 'Unable to pdo-connect to database on "' . $host . '" as ' . $user . '<br />';
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
    private function initCurrentPage()
    {
        if (!$this->connectConfigExists()) {
            return $this->page = 'login';
        }
        $defaultPage = 'tbl_list';
        if (config('tblliststart') == '0' || !$this->db) {
            $defaultPage = 'db_list';
        }
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
    public function getMessagesData()
    {
        if ($this->allowRepeatMessages == '' && !isAjax()) {
            $messages = array_count_values($this->messages);
            $this->messages = array_unique($this->messages);
            foreach ($this->messages as $k => $message) {
                if ($messages[$message] > 1) {
                    $this->messages [$k] .= ' (' . $messages[$message] . ')';
                }
            }
        }
        return $this->messages;
    }


    /**
     * Ошибка
     *
     * @param string $text
     * @param null $sql
     */
    public function error(string $text, $sql = null): bool
    {
        return $this->addMessage($text, $sql, MS_MSG_FAULT, $this->error);
    }

    /**
     * Успешная операция
     *
     * @param string $text
     * @param null $sql
     */
    public function success(string $text, $sql = null): bool
    {
        return $this->addMessage($text, $sql, MS_MSG_SUCCESS);
    }

    /**
     * Заметка
     *
     * @param string $text
     * @param null $sql
     */
    public function notice(string $text, $sql = null): bool
    {
        return $this->addMessage($text, $sql, MS_MSG_NOTICE);
    }

    /**
     * Сохраняет важное сообщение о процессе выполнения, которое будет выведено пользователю
     *
     * @param string  текст сообщения
     * @param string  sql запрос
     * @param integer тип сообщения MS_MSG_[SIMPLE SUCCESS FAULT ERROR NOTICE]
     * @return boolean
     */
    private function addMessage($text, $sql = null, $type = MS_MSG_SIMPLE, $error = ''): bool
    {
        $textError = $text;
        if ($sql != '') {
            $aff = '';
            if ($this->affectedRows) {
                $aff = '<br /><span style="color:#ccc">затронуто рядов: ' . $this->affectedRows . '</span>';
            }
            $text .= '<div class="sqlQuery">' . wordwrap(htmlspecialchars($sql), 200) . ';' . $aff . '</div>';
            if ($error != null) {
                $text .= '<div class="mysqlError"><b>Ошибка:</b> ' . $error . '</div>';
            }
        }
        $colors = [
            MS_MSG_SIMPLE => 'black',
            MS_MSG_SUCCESS => 'green',
            MS_MSG_FAULT => 'red',
            MS_MSG_ERROR => 'darkred',
            MS_MSG_NOTICE => 'blue'
        ];
        $color = $colors[$type] ?? 'black';
        if (isAjax()) {
            $this->messages [] = [
                'text' => $textError,
                'type' => $type,
                'color' => $color,
                'error' => $error,
                'sql' => $sql,
                'rows' => $this->affectedRows,
            ];
        } else {
            $this->messages [] = '<span style="color:' . $color . '">' . $text . '</span>';
        }
        if ($type == MS_MSG_ERROR || $type == MS_MSG_FAULT) {
            return false;
        }
        return true;
    }

}
