<?php

/**
 *
 */
class DatabaseQuery
{
    public int $affectedRows = 0;
    public string $lastSql = '';
    public string $error = '';
    public bool $exceptionOnError = true;

    /**
     * Единый для всех запрос в БД
     *
     * @param string $sql
     * @return bool
     * @throws Exception
     */
    public function execPdo(string $sql)
    {
        return $this->queryPdo('exec', $sql);
    }

    /**
     * @return false|int|PDOStatement
     * @throws Exception
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
    private function queryPdo($mode, string $sql)
    {
        global $pdo;
        if (!$pdo) {
            throw new Exception($sql);
        }
        try {
            $this->lastSql = $sql;
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
            logError($this->error, $sql);
            if ($this->exceptionOnError && !isAjax()) {
                // При USE ошибка перехватывается и выводится другой html
                if (!str_starts_with($sql, 'USE')) {
                    echo '<pre>';
                }
                throw new Exception($this->error);
            }
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
    public function getData($sql, $type = PDO::FETCH_ASSOC): array
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
     * @throws Exception
     */
    public function selectDb($db): bool
    {
        if ($db == null) {
            return false;
        }
        try {
            $this->driver->selectDb($db);
        } catch (\Exception $e) {
            $this->error('Ошибка при выборе базы данных "' . $db . '": '. $e->getMessage());
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
    protected function loqQuery($sql, $result = null)
    {
        if (!$result || preg_match('~^(SHOW|SELECT|SET)~i', trim($sql))) {
            return false;
        }
        $string = trim($sql);
        $string = preg_replace('/[\r\n\t]+/', ' ', $string);
        $string = str_replace('  ', ' ', $string);
        logInFile($string, $this->db);
    }

    private $logEnabled = true;
    public function disableLog()
    {
        $this->logEnabled = false;
    }
    public function enableLog()
    {
        $this->logEnabled = true;
    }
}
