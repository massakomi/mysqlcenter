<?php

/**
 * Класс, отвечающий за работу с базой данных
 */
class DatabaseManager
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
     * Удаляет / создаёт БД
     *
     * @param string База данных
     * @param string DROP|CREATE
     * @return boolean
     */
    function DatabaseAction($db, $type = 'DROP')
    {
        global $msc;
        $this->validate->queryCheck($db);
        switch ($type) {
            case 'DROP'   :
                $sql = "DROP DATABASE `$db`";
                $text = 'удалена';
                break;
            case 'CREATE' :
                $sql = "CREATE DATABASE `$db`";
                $text = 'создана';
                break;
            default :
                return $msc->addMessage('Неверный тип обработки', null, MS_MSG_ERROR);
        }
        if ($msc->execPdo($sql)) {
            return $msc->addMessage("База данных $db $text", $sql, MS_MSG_SUCCESS);
        } else {
            return $msc->addMessage("Ошибка работы с $db", $sql, MS_MSG_FAULT, $msc->error);
        }
    }

    /**
     * Удаляет / очищает все таблицы БД
     *
     * @param string База данных
     * @param boolean Если true - удалить таблицы, иначе очистить
     * @return boolean true только если ошибок нет, false - если хотя бы одна таблица не обработана
     */
    function DatabaseTruncate($db, $delete = false)
    {
        global $msc;
        $dbt = new DatabaseTable();
        $this->validate->queryCheck($db);
        $a = DatabaseTable::getTables($db);
        if (count($a) == 0) {
            return $msc->addMessage('Таблиц нет', null, MS_MSG_FAULT);
        }
        $errors = 0;
        foreach ($a as $t) {
            if ($delete) {
                if (!$dbt->tableAction($db, $t, 'DROP')) {
                    $errors++;
                }
            } elseif ($dbt->tableAction($db, $t, 'TRUNCATE')) {
                $errors++;
            }
        }
        return ($errors == 0);
    }

    /**
     * Копирует / переименовывает БД
     *
     * @param string БД-источник
     * @param string БД, куда копируется/перемещается
     * @param boolean Если true, то перемещает, иначе копирует
     * @param boolean Если true, копирует структуру (CREATE TABLE...), иначе нет
     * @param boolean Если true, копирует данные, иначе нет
     * @return boolean
     */
    function DatabaseCopy($dbFrom, $dbTo, $isMove = false, $struct = true, $data = true)
    {
        if ($this->DatabaseAction($dbTo, 'CREATE')) {
            $dbt = new DatabaseTable();
            // скопировать все таблицы туда и удалить из старой БД
            $a = DatabaseTable::getTables($dbFrom);
            foreach ($a as $table) {
                $dbt->copyTable($dbFrom, $table, $struct, $data, $table, $dbTo);
                if ($isMove) {
                    //$dbt->tableAction($dbFrom, $table, 'DROP');
                }
            }
            // удалить БД
            if ($isMove) {
                return $this->DatabaseAction($dbFrom, 'DROP');
            }
            return true;
        }
        return false;
    }

    /**
     * Изменяет кодировку или сравнение БД
     *
     * @param string БД
     * @param string Кодировка
     * @param boolean Если true, то меняется кодировка, иначе сравнение
     * @return boolean
     */
    function DatabaseAlterCharset($db, $charset, $isCharset = true)
    {
        global $msc;
        $this->validate->queryCheck($db, 'table', $charset);
        if (!$isCharset) {
            $sql = "ALTER DATABASE $db COLLATE `$charset`";
        } else {
            $sql = "ALTER DATABASE $db CHARACTER SET `$charset`";
        }
        return $msc->execPdo($sql);
    }
}
