<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/*
 * Используется для определения общих методов и параметров, используемых всеми классами
 *
 * Для использования классов предполагается наличие некоторого класса и его глобально определённого объекта
 * В этом объекте должна быть функция, которая отвечает за запрос к базе (типа mysql_query)
 * Также должен быть объект, и метод этого объекта, для получение сообщений и кодов ошибок.
 * Эти два объекта является связующим звеном между реализацией возможностей этими классами и внешней средой.
*/


/**
 * Общий класс для всех классов работы с базой данных
 */
class DatabaseInterface
{


    // INTERFACE

    /**
     * Имя глобального объекта, метод которого будет использоваться при запросе к базе данных
     * Для установки этого значения можно также использовать константу DBI_MSC_QUERY_OBJECT
     * @public string
     */
    public $queryObject;

    /**
     * Имя метода объекта $queryObject, которому будут передаваться sql запросы для выполнения
     * Для установки этого значения можно также использовать константу DBI_MSC_QUERY_METHOD
     * @public string
     */
    public $queryMethod;

    /**
     * Имя глобального объекта, метод которого будет использоваться для передачи сообщений и кодов ошибок
     * Для установки этого значения можно также использовать константу DBI_MSC_MSG_OBJECT
     * @public string
     */
    public $msgObject;

    /**
     * Имя метода объекта $msgObject, которому будут передаваться сообщения и коды ошибок
     * Для установки этого значения можно также использовать константу DBI_MSC_MSG_METHOD
     * @public string
     */
    public $msgMethod;


    // REALIZATION

    /**
     * Инициализирующая функция. Вызывается только в конструкторе классов-потомков.
     * Используется для переноса значений специальных констант в переменные объекта.
     * @parentClass DatabaseInterface
     */
    public function _init()
    {
        if (defined('DBI_MSC_QUERY_OBJECT')) {
            $this->queryObject = DBI_MSC_QUERY_OBJECT;
        }
        if (defined('DBI_MSC_QUERY_METHOD')) {
            $this->queryMethod = DBI_MSC_QUERY_METHOD;
        }
        if (defined('DBI_MSC_MSG_OBJECT')) {
            $this->msgObject = DBI_MSC_MSG_OBJECT;
        }
        if (defined('DBI_MSC_MSG_METHOD')) {
            $this->msgMethod = DBI_MSC_MSG_METHOD;
        }
    }

    /**
     * Запрос к БД
     *
     * @parentClass DatabaseInterface
     * @param string
     * @param string
     * @return resource
     */
    public function query($sql, $database = null)
    {
        global $connection;
        if ($this->queryObject != '' && $this->queryMethod != '') {
            if (!isset($GLOBALS[$this->queryObject])) {
                exit('Query object must be global');
            }
            if (!is_object($GLOBALS[$this->queryObject])) {
                exit('Query object must be object');
            }
            return call_user_func(array($GLOBALS[$this->queryObject], $this->queryMethod), $sql, $database);
        }
        return mysqli_query($connection, $sql);
    }

    /**
     * Сообщение
     *
     * @parentClass DatabaseInterface
     * @param string  Текст ошибки
     * @param string  SQL запрос
     * @param integer Код ошибки
     * @param string  Mysql Error строка
     */
    public function addMessage($text, $sql = null, $type = null, $mysqlError = null)
    {
        if ($this->msgObject != '' && $this->msgMethod != '') {
            if (!isset($GLOBALS[$this->msgObject])) {
                exit('msg object must be global');
            }
            if (!is_object($GLOBALS[$this->msgObject])) {
                exit('msg object must be object');
            }
            return call_user_func(array($GLOBALS[$this->msgObject], $this->msgMethod), $text, $sql, $type, $mysqlError);
        }
    }

    /**
     * Проверка наличия в запросе БД, таблицы или чего-то ещё. Ловит ошибку самого высокого уровня, поэтому безусловный exit()
     *
     * @parentClass DatabaseInterface
     * @param string база данных
     * @param string таблица
     * @param string прочие требуемые параметры (третий, четвертый и другие параметры функции)
     */
    public function queryCheck($database = '', $table = '', $params = '')
    {
        $args = func_get_args();
        if (isset($args[0]) && is_null($args[0]) || empty($args[0])) {
            $this->error('Database name not defined');
        }
        if (isset($args[1]) && is_null($args[1]) || count($args) > 1 && empty($args[1])) {
            $this->error('Table name not defined');
        }
        if (count($args) > 2) {
            foreach ($args as $num => $value) {
                if ($num < 2) {
                    continue;
                }
                if (is_null($value)) {
                    $this->error('Some reqired query param not defined');
                }
            }
        }
    }

    /**
     * Сообщение о серьезной ошибке выводится сразу на печать и exit
     *
     * @parentClass DatabaseInterface
     * @param string
     */
    public function error($message, $file = null, $line = null, $db_error = false)
    {
        echo $message;
        if ($file != null && $line != null) {
            echo "<br /><b>file:</b> $file<br /><b>line:</b> $line";
        }
        exit;
    }
}
