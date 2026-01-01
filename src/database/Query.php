<?php

declare(strict_types=1);

namespace database;

use enum\FetchType;

/**
 *
 */
class Query
{
    public int $affectedRows = 0;
    public string $lastSql = '';
    public string $error = '';
    public bool $exceptionOnError = true;
    public ?Driver $driver = null;
    public string $driverName = '';

    /**
     * Единый для всех запрос в БД
     *
     * @param string $sql
     * @return bool
     * @throws \Exception
     */
    public function execPdo(string $sql)
    {
        return $this->queryPdo(FetchType::Exec, $sql);
    }

    /**
     * @return false|int|\PDOStatement
     * @throws \Exception
     */
    public function fetchPdo(string $sql)
    {
        return $this->queryPdo(FetchType::Fetch, $sql);
    }

    /**
     * @param $mode
     * @param string $sql
     * @return false|int|\PDOStatement
     */
    private function queryPdo(FetchType $mode, string $sql)
    {
        global $pdo;
        if (!$pdo) {
            throw new \Exception($sql);
        }
        try {
            if ($this->driverName == 'pgsql') {
                $sql = str_replace('`', '"', $sql);
            }
            $this->error = '';
            $this->lastSql = $sql;
            $this->affectedRows = 0;
            if ($mode == FetchType::Exec) {
                $this->affectedRows = $pdo->exec($sql);
                $result = true;
            } else {
                $result = $pdo->query($sql, \PDO::FETCH_ASSOC);
            }
        } catch (\PDOException $e) {
            $result = false;
            // $pdo->errorInfo()[2]; последняя ошибка, не текущая
            $this->error = $e->getMessage();
            logError($this->error, $sql);
            if ($this->exceptionOnError && !isAjax()) {
                // При USE ошибка перехватывается и выводится другой html
                if (!str_starts_with($sql, 'USE')) {
                    echo '<pre>';
                }
                throw new \Exception($this->error);
            }
        }
        if ($this->logEnabled) {
            $this->loqQuery($sql, $result);
        }
        return $result;
    }

    /**
     * @param $sql
     * @param bool|int $type
     * @return array
     * @throws \Exception
     */
    public function getData($sql, bool|int $type = \PDO::FETCH_ASSOC): array
    {
        if (!is_numeric($type)) {
            $type = \PDO::FETCH_ASSOC;
        }
        $res = $this->fetchPdo($sql);
        if (!$res) {
            return [];
        }
        return $res->fetchAll($type);
    }

    /**
     * Выполняет выбор БД (select_db) на сервера
     * @param string $db
     * @throws \Exception
     */
    public function selectDb(string $db): bool
    {
        if ($db == null) {
            return false;
        }
        try {
            $this->driver->selectDb($db);
        } catch (\Exception $e) {
            $this->error('Ошибка при выборе базы данных "' . $db . '": ' . $e->getMessage());
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
    protected function loqQuery($sql, $result = null): bool
    {
        if (!$result || preg_match('~^(SHOW|SELECT|SET)~i', trim($sql))) {
            return false;
        }
        $string = trim($sql);
        $string = preg_replace('/[\r\n\t]+/', ' ', $string);
        $string = str_replace('  ', ' ', $string);
        logInFile($string, $this->db);
        return true;
    }

    private $logEnabled = true;
    public function disableLog(): void
    {
        $this->logEnabled = false;
    }
    public function enableLog(): void
    {
        $this->logEnabled = true;
    }

    /**
     * @throws \Exception
     */
    public function error(string $text)
    {
        throw new \Exception($text);
    }
}
