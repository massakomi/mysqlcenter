<?php


/**
 *
 */
class DatabaseQuery
{
    public $affectedRows;

    /**
     * Единый для всех запрос в БД
     *
     * @param string $sql
     * @return false|int|PDOStatement
     */
    public function execPdo(string $sql)
    {
        return $this->queryPdo('exec', $sql);
    }

    /**
     * @return false|int|PDOStatement
     */
    public function fetchPdo(string $sql)
    {
        return $this->queryPdo('fetch', $sql);
    }

    /**
     * @return false|int|PDOStatement
     */
    public function fetchPdoObject(string $sql)
    {
        return $this->queryPdo('fetchObject', $sql);
    }

    /**
     * @param $mode
     * @param string $sql
     * @return false|int|PDOStatement
     */
    private function queryPdo($mode, string $sql) {
        global $pdo;
        try {
            $this->affectedRows = 0;
            if ($mode == 'exec') {
                $result = $pdo->exec($sql);
                $this->affectedRows = $result;
                $result = true;
            } elseif ($mode == 'fetchObject') {
                $result = $pdo->query($sql, PDO::FETCH_OBJ);
            } else {
                $result = $pdo->query($sql, PDO::FETCH_ASSOC);
            }
        } catch (\PDOException $e) {
            // $pdo->errorInfo()[2]; последняя ошибка, не текущая
            $this->error = $e->getMessage();
            //echo $this->error."<br />";
            msclog('query()', $sql);
        }
        if ($this->logEnabled) {
            $this->loqQuery($sql, $result);
        }
        return $result;
    }

    /**
     * @param $sql
     * @param bool $type
     * @return array
     */
    public function getData($sql, $type=PDO::FETCH_ASSOC): array
    {
        if (!is_numeric($type)) {
            $type = PDO::FETCH_ASSOC;
        }
        $res = $this->fetchPdo($sql);
        if (!$res) {
            return [];
        }
        return $res->fetchAll($type);
    }

    /**
     * Выполняет выбор БД (select_db) на сервера
     * @param string
     */
    function selectDb($db): bool
    {
        if ($db == null) {
            return false;
        }
        try {
            $this->execPdo("USE `$db`");
        } catch (\Exception $e) {
            $this->addMessage('Ошибка при выборе базы данных "' . $db . '"', '', MS_MSG_FAULT, $this->error);
            return false;
        }
        return true;
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

    private $logEnabled = true;
    function disableLog() {
        $this->logEnabled = false;
    }
    function enableLog() {
        $this->logEnabled = true;
    }
}