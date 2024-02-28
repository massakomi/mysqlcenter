<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

// типы сообщений
define('MS_MSG_SIMPLE',  1); // инфо
define('MS_MSG_SUCCESS', 2); // успешная операция
define('MS_MSG_FAULT',   3); // операция не удалась
define('MS_MSG_ERROR',   4); // серъёзная ошибка
define('MS_MSG_NOTICE',  5); // непонятная ситуация, замечание

/**
 * Управляющий класс
 * Он отвечает за следующие действия:
 * - сообщения
 * - заголовок раздела h1 и страницы title
 * - текущая страница, БД, таблица
 */
class MSCenter
{

    // public
    var $db, $table, $page;

    /**
     * Массив запросов общий
     */
    var $queries = array();

    // private
    var $dbSelected;
    var $messages = array();

    /**
     * Общедоступная переменная для создания заголовка раздела h1
     */
    var $pageTitle;

    /**
     * Время timestamp начала работы программы. Используется для подсчёта времени выполнения.
     */
    var $timer;

    var $allowRepeatMessages;

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
    function getCurrentDatabase()
    {
        global $connection;
        if (GET('db') != '') {
            if ($this->db != GET('db')) {
                setcookie('mc_db', GET('db'), time() + 3600 * 24 * 14, '/');
            }
            $this->db = GET('db');
        } elseif (!empty($_SESSION['db'])) {
            $this->db = $_SESSION['db'];
        } elseif (!empty($_COOKIE['mc_db'])) {
            $this->db = $_COOKIE['mc_db'];
        } else {
            if ($this->db != DB_NAME) {
                setcookie('mc_db', DB_NAME, time() + 3600 * 24 * 14, '/');
            }
            $this->db = DB_NAME;
        }
        if (!$this->db && DB_NAME) {
            $this->db = DB_NAME;
        }
        if (!$this->db) {
            $this->addMessage('Не выбрана база данных', '', MS_MSG_FAULT, mysqli_error($connection));
            return $this->db = null;
        }
        if (!$this->selectDb($this->db)) {
            $this->addMessage('Ошибка при выборе базы данных "' . $this->db . '"', '', MS_MSG_FAULT, mysqli_error($connection));
            return $this->db = null;
        }
        $this->query('SET collation_connection = ' . MS_COLLATION);
        //$this->query('SET collation_database = '.MS_COLLATION);
        //$this->query('SET collation_server = '.MS_COLLATION);
        $this->query('SET NAMES "' . MS_CHARACTER_SET . '"');
        $this->query('SET character_set_server = ' . MS_CHARACTER_SET);
        $this->query('SET sql_mode=""');
        return $this->db;
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
    function getMessagesData() {
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
        global $connection;
        $this->addMessage($text, $sql, MS_MSG_NOTICE, mysqli_error($connection));
    }

    /**
     * Сохраняет важное сообщение о процессе выполнения, которое будет выведено пользователю
     *
     * @param string  текст сообщения
     * @param string  sql запрос
     * @param integer тип сообщения MS_MSG_[SIMPLE SUCCESS FAULT ERROR NOTICE]
     * @return boolean
     */
    function addMessage($text, $sql = null, $type = MS_MSG_SIMPLE, $error='')
    {
        global $connection;
        if (!$error) {
            $error = mysqli_error($connection);
        }
        $textError = $text;
        if ($sql != '') {
            $aff = '<br /><span style="color:#ccc">затронуто рядов: ' . mysqli_affected_rows($connection) . '</span>';
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
            $this->messages []= [
                'text' => $textError,
                'type' => $type,
                'color' => $color,
                'error' => $error,
                'sql' => $sql,
                'rows' => mysqli_affected_rows($connection),
            ];
        } else {
            $this->messages []= '<span style="color:' . $color . '">' . $text . '</span>';
        }
        if ($type == MS_MSG_ERROR || $type == MS_MSG_FAULT) {
            return false;
        }
        return true;
    }

    /**
     * Единый для всех запрос в БД
     *
     * @param string
     * @param string
     * @param boolean
     * @return mysqli_result mysql
     */
    public function query($sql, $database = null, $log = true)
    {
        global $connection, $msc;
        if ($database != null) {
            $this->selectDb($database);
        }
        $this->queries [] = $sql;
        try {
            $result = mysqli_query($connection, $sql);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            msclog('query()', $sql);
            $msc->addMessage($this->error, null, MS_MSG_FAULT);;
        }
        if ($log) {
            $this->loqQuery($sql, $result);
        }
        return $result;
    }

    /**
     * @param $sql
     * @param null $result
     * @return array
     */
    public function getData($sql, $result = null)
    {
        $res = $this->query($sql);
        $data = mysqli_fetch_all($res, MYSQLI_ASSOC);
        return $data;
    }

    /**
     * Перехватывает запрос и сохраняет его в файле
     *
     * @access private
     * @param $sql
     * @param null $result
     * @return bool
     */
    public function loqQuery($sql, $result = null)
    {
        if (!MS_LOG_ALLOW) {
            return false;
        }
        if (!$result || preg_match('~^(SHOW|SELECT|SET)~i', trim($sql))) {
            return false;
        }
        $string = trim($sql);
        $string = preg_replace('/[\r\n\t]+/', ' ', $string);
        $string = str_replace('  ', ' ', $string);
        $this->logInFile($string);
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

    /**
     * Выполняет дамп, разделённый ;\r\n (sql.php) Лог запросов производит сразу
     *
     * @param string $sql
     * @param string $database
     * @return boolean
     */
    public function exec($sql, $database)
    {
        global $connection;
        if ($sql == null || empty($sql)) {
            return $this->addMessage('Запрос пустой', null, MS_MSG_FAULT);
        }
        $this->logInFile($sql);
        $ret = array();
        $this->PMA_splitSqlFile($ret, $sql);
        $succ = 0;
        $this->selectDb($database);
        $errors = array();
        foreach ($ret as $v) {
            if ($this->query($v['query'], null, false)) {
                $succ++;
            } else {
                $errors [] = mysqli_error($connection);
            }
        }
        $fault = count($errors);
        $info = " $succ запросов выполнено, $fault неудач. ";
        if ($fault == 0) {
            return $this->addMessage('Запрос выполнен без ошибок - ' . $info, null, MS_MSG_SUCCESS);
        } else {
            return $this->addMessage('Запросы выполнены с ошибками' . $info, null, MS_MSG_FAULT, implode('<br />', $errors));
        }
    }

    /**
     * Удаляет комментарии и разделяет большие sql файлы в индивидуальные запросы
     *
     * @access private
     * @param array    Массив, куда помещать запросы
     * @param string   SQL запросы, разделенный точкой с запятой
     * @return boolean  всегда true
     */
    function PMA_splitSqlFile(&$ret, $sql)
    {
        // do not trim, see bug #1030644
        //$sql      = trim($sql);
        $sql = rtrim($sql, "\n\r");
        $sql_len = strlen($sql);
        $char = '';
        $string_start = '';
        $in_string = FALSE;
        $nothing = TRUE;
        $time0 = time();

        for ($i = 0; $i < $sql_len; ++$i) {
            $char = $sql[$i];

            // We are in a string, check for not escaped end of strings except for
            // backquotes that can't be escaped
            if ($in_string) {
                for (; ;) {
                    $i = strpos($sql, $string_start, $i);
                    // No end of string found -> add the current substring to the
                    // returned array
                    if (!$i) {
                        $ret[] = array('query' => $sql, 'empty' => $nothing);
                        return TRUE;
                    }
                    // Backquotes or no backslashes before quotes: it's indeed the
                    // end of the string -> exit the loop
                    else if ($string_start == '`' || $sql[$i - 1] != '\\') {
                        $string_start = '';
                        $in_string = FALSE;
                        break;
                    } // one or more Backslashes before the presumed end of string...
                    else {
                        // ... first checks for escaped backslashes
                        $j = 2;
                        $escaped_backslash = FALSE;
                        while ($i - $j > 0 && $sql[$i - $j] == '\\') {
                            $escaped_backslash = !$escaped_backslash;
                            $j++;
                        }
                        // ... if escaped backslashes: it's really the end of the
                        // string -> exit the loop
                        if ($escaped_backslash) {
                            $string_start = '';
                            $in_string = FALSE;
                            break;
                        } // ... else loop
                        else {
                            $i++;
                        }
                    } // end if...elseif...else
                } // end for
            } // end if (in string)

            // lets skip comments (/*, -- and #)
            else if (($char == '-' && $sql_len > $i + 2 && $sql[$i + 1] == '-' && $sql[$i + 2] <= ' ') || $char == '#' || ($char == '/' && $sql_len > $i + 1 && $sql[$i + 1] == '*')) {
                $i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
                // didn't we hit end of string?
                if ($i === FALSE) {
                    break;
                }
                if ($char == '/') $i++;
            } // We are not in a string, first check for delimiter...
            else if ($char == ';') {
                // if delimiter found, add the parsed part to the returned array
                $ret[] = array('query' => substr($sql, 0, $i), 'empty' => $nothing);
                $nothing = TRUE;
                $sql = ltrim(substr($sql, min($i + 1, $sql_len)));
                $sql_len = strlen($sql);
                if ($sql_len) {
                    $i = -1;
                } else {
                    // The submited statement(s) end(s) here
                    return TRUE;
                }
            } // end else if (is delimiter)

            // ... then check for start of a string,...
            else if (($char == '"') || ($char == '\'') || ($char == '`')) {
                $in_string = TRUE;
                $nothing = FALSE;
                $string_start = $char;
            } // end else if (is start of string)

            elseif ($nothing) {
                $nothing = FALSE;
            }

            // loic1: send a fake header each 30 sec. to bypass browser timeout
            $time1 = time();
            if ($time1 >= $time0 + 30) {
                $time0 = $time1;
                header('X-pmaPing: Pong');
            }
        }

        // add any rest to the returned array
        if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql)) {
            $ret[] = array('query' => $sql, 'empty' => $nothing);
        }

        return TRUE;
    }

    /**
     * Выполняет выбор БД (select_db) на сервера
     * @param string
     */
    function selectDb($db)
    {
        global $connection;
        if ($db == null) {
            return false;
        }
        if ($this->dbSelected == $db) {
            return true;
        }
        try {
            mysqli_select_db($connection, $db);
        } catch (\Exception $e) {
            return false;
        }
        $this->db = $db;
        return true;
    }

}
?>
