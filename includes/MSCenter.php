<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

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

    // public
    public $db, $table, $page;

    // private
    public array $messages = [];

    /**
     * Общедоступная переменная для создания заголовка раздела h1
     */
    public string $pageTitle = '';

    /**
     * Время timestamp начала работы программы. Используется для подсчёта времени выполнения.
     */
    public float $timer;

    public $allowRepeatMessages;

    /**
     * Конструктор, для начала анализа скорости
     * @access private
     */
    function __construct()
    {
        $this->timer = round(array_sum(explode(" ", microtime())), 10);
    }

    /**
     * Инициализация - отдельно от конструтора, чтобы тот раньше запустился
     * @access private
     */
    function init()
    {
        $this->db = $this->getCurrentDatabase();
        $this->table = $this->getCurrentTable();
        $this->page = $this->getCurrentPage();
    }

    /**
     * Возвращает заголовок страницы, вызывается только в основном шаблоне
     * @access private
     */
    function getPageTitle()
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
    function getWindowTitle()
    {
        $mainTitle = null;
        $mainTitle .= $this->table != null ? "$this->table < " : null;
        $mainTitle .= $this->db != null ? $this->db : $this->page;
        if ($mainTitle == '') {
            $mainTitle .= MS_APP_NAME . ' ' . MS_APP_VERSION;
        }
        return $mainTitle;
    }

    /**
     * Возвращает текущую отображаемую базу данных (которую мы видим), вызывается при инициализации
     * @access private
     */
    public function getCurrentDatabase()
    {
        if (!$this->connected()) {
            return null;
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
        if (!$this->db) {
            return $this->db = null;
        }
        return $this->db;
    }

    public function clearCurrentDatabase(): void
    {
        setcookie('mc_db', null, -1, '/');
        $_SESSION['db'] = '';
        $this->db = null;
    }

    /**
     * @return bool
     */
    public function connected(): bool
    {
        return defined('DB_HOST');
    }

    /**
     * Возвращает текущую таблицу, вызывается при инициализации
     * @access private
     */
    function getCurrentTable()
    {
        if ($this->table == null && $this->db != null) {
            $this->table = GET('table');
        }
        return $this->table;
    }

    /**
     * Возвращает алиас текущего раздела, вызывается при инициализации
     * @access private
     */
    function getCurrentPage()
    {
        if (!$this->connected()) {
            return $this->page = 'login';
        }
        $defaultPage = 'tbl_list';
        if (conf('tblliststart') == '0' || !$this->db) {
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
        return $this->page;
    }

    /**
     * @return array
     */
    function getMessagesData()
    {
        if ($this->allowRepeatMessages == '' && !isajax()) {
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
     * Возвращает блок накопленных за время выполнения скрипта сообщений
     * @return string
     */
    function getMessages()
    {
        $messages = $this->getMessagesData();
        if (count($messages) == 0) {
            return null;
        }
        $messageId = "mid" . time();    // если много сообщений
        $s =
            '<table class="globalMessage">' .
            '  <tr><th>Сообщение <a href="#" class="hiddenSmallLink" style="color:#fff" onClick="showhide(\'' . $messageId . '\')">close</a></th></tr>' .
            '  <tr id="' . $messageId . '"><td>' . implode('<br />', $this->messages) . '  </td></tr>' .
            '</table>';
        if (conf('hidemessages') == '1') {
            $s .= '
<script language="javascript">
showhide("' . $messageId . '");
</script>
            ';
        }
        return $s;
    }

    /**
     * Ошибка очень серьёзная - exit
     *
     * @param string
     * @param string
     * @param integer
     */
    function error($message, $file = null, $line = null)
    {
        echo $message;
        if ($file != null && $line != null) {
            echo "<br /><b>file:</b> $file<br /><b>line:</b> $line";
        }
        exit;
    }

    /**
     * Замечание
     *
     * @param string
     */
    function notice($text, $sql = null)
    {
        $this->addMessage($text, $sql, MS_MSG_NOTICE, $this->error);
    }

    /**
     * Сохраняет важное сообщение о процессе выполнения, которое будет выведено пользователю
     *
     * @param string  текст сообщения
     * @param string  sql запрос
     * @param integer тип сообщения MS_MSG_[SIMPLE SUCCESS FAULT ERROR NOTICE]
     * @return boolean
     */
    function addMessage($text, $sql = null, $type = MS_MSG_SIMPLE, $error = ''): bool
    {
        $textError = $text;
        if ($sql != '') {
            $aff = '<br /><span style="color:#ccc">затронуто рядов: ' . $this->affectedRows . '</span>';
            $text .= '<div class="sqlQuery">' . wordwrap(htmlspecialchars($sql), 200, "\r\n") . ';' . $aff . '</div>';
            if ($error != null) {
                $text .= '<div class="mysqlError"><b>Ошибка:</b> ' . $error . '</div>';
            }
        }
        $colors = array(
            MS_MSG_SIMPLE => 'black',
            MS_MSG_SUCCESS => 'green',
            MS_MSG_FAULT => 'red',
            MS_MSG_ERROR => 'darkred',
            MS_MSG_NOTICE => 'blue'
        );
        $color = isset($colors[$type]) ? $colors[$type] : 'black';
        if (isajax()) {
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

    /**
     * Прямая запись строки в лог (для множества запросов в sql разделе)
     *
     * @access private
     * @param $string
     * @return bool|void
     */
    public function logInFile($string)
    {
        if (conf('sqllog') != '1') {
            return;
        }
        $string .= ";\r\n";
        if (!file_exists(DIR_MYSQL . 'data')) {
            if (!@mkdir(DIR_MYSQL . 'data', 0777)) {
                return $this->addMessage('Не смог создать папку data', null, MS_MSG_FAULT);
            }
        }
        $file = DIR_MYSQL . 'data/' . $this->db . '.sql';
        if (!$fo = @fopen($file, file_exists($file) ? 'a+' : 'w+')) {
            return false;
        }
        $result = fwrite($fo, $string);
        fclose($fo);
        if (!$result) {
            return $this->addMessage('Не смог создать/записать файл "' . $file . '"', null, MS_MSG_FAULT);
        }
    }

    private $popularTablesFile = 'docs/popular.json';

    public function getPopularTables(): array
    {
        if (!file_exists($this->popularTablesFile) || !$this->db) {
            return [];
        }
        $json = file_get_contents($this->popularTablesFile);
        $json = json_decode($json, true);
        if (!array_key_exists($this->db, $json)) {
            $json[$this->db] = [];
        }
        if ($_GET['resetPopular']) {
            unset($json[$this->db]);
            file_put_contents($this->popularTablesFile, json_encode($json));
        }
        ksort($json[$this->db]);
        foreach ($json[$this->db] as $table => $values) {
            if (!is_array($values)) {
                $json[$this->db][$table] = ['count' => $values];
            }
        }
        return $json;
    }

    /**
     * @return array
     */
    public function getPopularTablesDb(): array
    {
        $tables = $this->getPopularTables();
        if (array_key_exists($this->db, $tables)) {
            return $tables[$this->db];
        } else {
            return [];
        }
    }

    /**
     * @param $table
     * @return void
     */
    public function addPopularTable($table)
    {
        $tables = $this->getPopularTables();
        if (array_key_exists($table, $tables[$this->db])) {
            $tables[$this->db] [$table]['count']++;
        } else {
            $tables[$this->db] [$table]['count'] = 1;
        }
        $tables[$this->db] [$table]['time'] = time();
        if (date('i') % 10 == 0) {
            $tablesAll = array_column(DatabaseTable::getCashedTablesArray(), 'Name');
            $exists = array_intersect(array_keys($tables[$this->db]), $tablesAll);
            $notExists = array_diff(array_keys($tables[$this->db]), $exists);
            if (count($notExists) > 0) {
                foreach ($notExists as $table) {
                    unset($tables[$this->db][$table]);
                }
            }
        }
        file_put_contents($this->popularTablesFile, json_encode($tables));
    }


    /**
     * Статистика просмотров баз данных
     * @return void
     */
    public function dbViewStat()
    {
        $dbs = Server::getDatabases();
        if (!in_array('mysqlcenter', $dbs) || empty($this->db)) {
            return;
        }
        $this->disableLog();
        $a = $this->getData('SELECT * FROM mysqlcenter.db_info WHERE db_name="' . $this->db . '"', PDO::FETCH_OBJ);
        if (count($a) == 0) {
            $this->execPdo('REPLACE INTO mysqlcenter.db_info VALUES("' . $this->db . '", 1, 1, "' . date('Y-m-d H:i:s') . '")');
        } else {
            $this->execPdo('UPDATE mysqlcenter.db_info SET views=views+1, last_view="' . date('Y-m-d H:i:s') . '" WHERE db_name="' . $this->db . '"');
        }

        // Статистика просмотров таблиц
        if ($this->table != '') {
            $a = $this->getData($t = 'SELECT * FROM mysqlcenter.table_info WHERE db_name="' . $this->db . '" AND table_name="' . $this->table . '"', PDO::FETCH_OBJ);
            if (count($a) == 0) {
                $this->execPdo('REPLACE INTO mysqlcenter.table_info VALUES("' . $this->db . '", "' . $this->table . '", 1, 1, "' . date('Y-m-d H:i:s') . '")');
            } else {
                $this->execPdo('UPDATE mysqlcenter.table_info SET views=views+1, last_view="' . date('Y-m-d H:i:s') .
                    '" WHERE db_name="' . $this->db . '" AND table_name="' . $this->table . '"');
            }
        }
        $this->enableLog();
    }
}