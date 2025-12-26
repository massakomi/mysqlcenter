<?php


/**
 * Класс, отвечающий за работу с таблицами базы данных
 */
class DatabaseTable
{

    public ?string $database;
    public ?string $table;
    private Validate $validate;

    /**
     * @access private
     */
    function __construct($db = null, $table = null)
    {
        $this->database = $db;
        $this->table = $table ? '`' . str_replace('`', '``', $table) . '`' : '';
        $this->validate = new Validate();
    }

    /**
     * Совершает действие типа $type с $table, используя если надо параметр $param
     *
     * @param string
     * @param string
     * @param string  DROP | TRUNCATE | ANALISE | OPTIMIZE | CHECK | REPAIR | FLUSH
     * @param string
     * @return boolean
     * @todo  проверка существования таблицы
     */
    public function tableAction($db, $table, $type = 'DROP', $param = null): bool
    {
        global $msc;
        $this->validate->queryCheck($db, $table);
        if (($type == 'RENAME' || $type == 'CHARSET' || $type == 'ORDER') && $param == null) {
            return $msc->addMessage('Не указан требуемый параметр', null, MS_MSG_ERROR);
        }
        switch ($type) {
            case 'DROP'        :
                $sql = "DROP TABLE `$table`";
                $text = 'удалена';
                break;
            case 'TRUNCATE'    :
                $sql = "TRUNCATE TABLE `$table`";
                $text = 'очищена';
                break;
            case 'CHECK'    :
                $sql = "CHECK TABLE `$table`";
                $text = 'обработана';
                break;
            case 'ANALYZE'    :
                $sql = "ANALYZE TABLE `$table`";
                $text = 'обработана';
                break;
            case 'REPAIR'    :
                $sql = "REPAIR TABLE `$table`";
                $text = 'обработана';
                break;
            case 'OPTIMIZE'    :
                $sql = "OPTIMIZE TABLE `$table`";
                $text = 'обработана';
                break;
            case 'FLUSH'    :
                $sql = "FLUSH TABLE `$table`";
                $text = 'обработана';
                break;
            case 'RENAME'    :
                $sql = "ALTER TABLE `$table` RENAME `$param`";
                $text = 'переименована';
                break;
            case 'CHARSET'    :
                $sql = "ALTER TABLE `$table` CONVERT TO CHARACTER SET `$param`";
                $text = 'изменена';
                break;
            case 'COMMENT'    :
                $sql = "ALTER TABLE `$table` COMMENT = '$param'";
                $text = 'изменена';
                break;
            case 'ORDER'    :
                $sql = "ALTER TABLE `$table` ORDER BY $param";
                $text = 'изменена';
                break;
            default :
                return $msc->addMessage('Неверный тип обработки', null, MS_MSG_ERROR);
        }
        $msc->selectDb($db);
        if ($msc->execPdo($sql)) {
            $msc->fetchPdo("ANALYZE TABLE `$table`;");
            return $msc->addMessage("Таблица $table $text", $sql, MS_MSG_SUCCESS);
        } else {
            return $msc->addMessage("Ошибка при выполнении операции с таблицей $table", $sql, MS_MSG_FAULT, $msc->error);
        }
    }

    /**
     * Копирует таблицу $table, если надо со структурой, данными. если надо переименовывает
     *
     * @param string  текущая БД
     * @param string  таблица
     * @param boolean надо ли создавать таблицу на новом месте
     * @param boolean надо ли копировать данные
     * @param string  новое имя, если таблица переименовывается
     * @param string  База данных, куда надо копировать
     * @return boolean
     */
    function copyTable($db, $table, $struct = true, $data = false, $newName = null, $database = null)
    {
        global $msc;
        $this->validate->queryCheck($db, $table);
        // дамп структуры
        if ($newName == null) {
            $newName = $table . '_copy';
        }
        if ($database == null) {
            $database = $db;
        }
        $exp = new Export();
        $exp->setDatabase($db);
        $exp->setTable($table);
        $sql = $exp->exportStructure(1, false);
        $sql = preg_replace('/CREATE TABLE ([a-zA-Z0-9_`\-]+)/i', 'CREATE TABLE `' . $newName . '`', $sql, 1);
        $msc->selectDb($database);
        if ($msc->execPdo($sql)) {
            $msc->addMessage("Таблица $table скопирована", $sql, MS_MSG_SUCCESS);
        } else {
            $msc->addMessage("Ошибка копирования $table", $sql, MS_MSG_FAULT, $msc->error);
            return false;
        }
        // переход в старую БД после запроса
        if ($database != $db) {
            $msc->selectDb($db);
        }
        // дамп данных
        if ($data) {
            if ($database != $db) {
                $sql = 'INSERT INTO ' . $database . '.' . $newName . ' SELECT * FROM ' . $db . '.' . $table;
            } else {
                $sql = "INSERT INTO $newName SELECT * FROM $table";
            }
            if ($msc->execPdo($sql)) {
                $msc->addMessage('Данные скопированы', $sql, MS_MSG_SUCCESS);
            } else {
                $msc->addMessage('Ошибка копирования данных', $sql, MS_MSG_FAULT, $msc->error);
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     */
    public static function getCashedTablesArray(): array
    {
        static $array;
        if (!isset($array)) {
            global $msc;
            $array = $msc->getData('SHOW TABLE STATUS', PDO::FETCH_OBJ);
        }
        return $array;
    }

    /**
     * Возвращает массив таблиц базы данных
     *
     * @param string База данных
     * @return array
     */
    public static function getTables($database = null)
    {
        global $msc;
        if (!is_null($database)) {
            $msc->selectDb($database);
        }

        $tables = DatabaseTable::getCashedTablesArray();
        $array = [];
        foreach ($tables as $o) {
            $array[] = $o->Name;
        }
        return $array;
    }

}

