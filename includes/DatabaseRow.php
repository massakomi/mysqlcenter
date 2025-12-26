<?php

/**
 * Класс, отвечающий за работу с рядами таблиц баз данных
 */
class DatabaseRow
{
    private Validate $validate;

    /**
     * @access private
     */
    function __construct()
    {
        $this->validate = new Validate();
    }

    /**
     * Удаляет $limit строк из $table по условию $row
     *
     * @param string
     * @param string
     * @param string $row
     * @param integer
     * @return boolean
     * @throws Exception
     */
    public function rowDelete($db, $table, $row, $limit = 1): bool
    {
        global $msc;
        $this->validate->queryCheck($db, $table, $row);
        $row = stripslashes(urldecode($row));
        $sql = 'DELETE FROM ' . $table . ' WHERE ' . $row . ' LIMIT ' . $limit;
        return $msc->execPdo($sql);
    }

    /**
     * Копирует строки $table по условию $row
     *
     * @param string
     * @param string
     * @param string
     * @return boolean
     */
    function rowCopy($table, $row)
    {
        global $msc;
        if (!$msc->getAutoIncrement($table)) {
            return $msc->addMessage('Невозможно скопировать ряд, т.к. в таблице нет поля auto_increment', null, MS_MSG_FAULT);
        }
        $fields = DatabaseTable::getFields($table, true);
        $row = stripslashes(urldecode($row));
        $sql = "INSERT INTO $table ($fields) SELECT $fields FROM $table WHERE $row";
        return $msc->execPdo($sql);
    }
}

