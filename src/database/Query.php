<?php

declare(strict_types=1);

namespace database;

use dto\ConnectConfig;
use enum\FetchType;

class Query
{
    public int $affectedRows = 0;
    public string $lastSql = '';
    public ?string $lastInsertId = null;
    public string $error = '';
    public string $db = '';
    public bool $exceptionOnError = true;
    public ?Driver $driver = null;
    public string $driverName = '';

    /**
     * Единый для всех запрос в БД.
     *
     * @throws \Exception
     */
    public function execPdo(string $sql): \PDOStatement|true|null
    {
        return $this->queryPdo(FetchType::Exec, $sql);
    }

    /**
     * @throws \Exception
     */
    public function fetchPdo(string $sql): ?\PDOStatement
    {
        $result = $this->queryPdo(FetchType::Fetch, $sql);

        return $result === true ? null : $result;
    }

    /**
     * @throws \Exception
     */
    private function queryPdo(FetchType $mode, string $sql): \PDOStatement|true|null
    {
        global $pdo, $msc;
        if (!$pdo) {
            echo '<pre>';
            throw new \Exception($sql);
        }
        $result = null;
        try {
            if ($this->driverName == 'pgsql') {
                $sql = str_replace('`', '"', $sql);
            }
            $this->lastInsertId = null;
            $this->error = '';
            $this->lastSql = $sql;
            $this->affectedRows = 0;
            if ($mode == FetchType::Exec) {
                $execResult = $pdo->exec($sql); // int false;
                if ($execResult !== false) {
                    $this->affectedRows = $execResult;
                    $result = true;
                    $this->saveLastInsertId();
                }
            } else {
                $result = $pdo->query($sql, \PDO::FETCH_ASSOC); // PDOStatement|false
            }
        } catch (\PDOException $e) {
            $this->logError($e, $sql);
        }
        if ($this->logEnabled && $result) {
            $this->loqQuery($sql);
        }

        return $result === false ? null : $result;
    }

    private function saveLastInsertId(): void
    {
        global $pdo;
        try {
            $id = $pdo->lastInsertId();
        } catch (\Throwable $e) {
            $id = false;
        }
        if ($id !== false) {
            $this->lastInsertId = $id;
        }
    }

    private function logError(\PDOException $e, string $sql): void
    {
        global $msc;
        $this->error = $e->getMessage();
        logError($this->error, $sql);
        if ($this->exceptionOnError && !isAjax()) {
            // При USE ошибка перехватывается и выводится другой html
            // if (!str_starts_with($sql, 'USE')) {
                //    echo '<pre>';
            // }
            // echo $sql;
            // echo '<hr />';
            // throw new \Exception($this->error);
            // Желательно показывать так, красиво и только в крайнем случае
            $msc->error($e->getTraceAsString(), $sql);
        }
    }

    /**
     * @return array<array<string>>
     *
     * @throws \Exception
     */
    public function getData(string $sql, int $type = \PDO::FETCH_ASSOC): array
    {
        return $this->fetchPdo($sql)?->fetchAll($type) ?? [];
    }

    /**
     * Выполняет выбор БД (select_db) на сервера.
     *
     * @throws \Exception
     */
    public function selectDb(string $db): bool
    {
        static $selectedDb;
        if ($db == null || (isset($selectedDb) && $selectedDb === $db)) {
            return false;
        }
        try {
            $this->driver->selectDb($db);
            $selectedDb = $db;
        } catch (\Exception $e) {
            $this->fatalError('Ошибка при выборе базы данных "'.$db.'": '.$e->getMessage());
        }

        return true;
    }

    /**
     * Закрывать соединение не нужно, оно автоматически закрывается при завершении скрипта или при обнулении
     * переменной $pdo.
     *
     * @param string|null $database опционально другая бд для подключения
     */
    public function connectPdo(ConnectConfig $config, ?string $database = null): void
    {
        global $pdo;
        $options = [
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8', collation_connection=".MS_COLLATION.
                ', character_set_server='.MS_CHARACTER_SET.', sql_mode=""',
        ];
        $database = $database ?: $config->database;
        $dsn = $config->driver.':host='.$config->host.';port='.$config->port.';dbname='.$database;
        $pdo = new \PDO($dsn, $config->user, $config->password, $options);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Перехватывает запрос и сохраняет его в файле.
     */
    protected function loqQuery(string $sql): bool
    {
        $sql = trim($sql);
        if (preg_match('~^(SHOW|SELECT|SET)~i', $sql)) {
            return false;
        }
        $string = preg_replace('/[\r\n\t]+/', ' ', $sql);
        $string = str_replace('  ', ' ', $string);
        logInFile($string, $this->db);

        return true;
    }

    private bool $logEnabled = true;

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
    public function fatalError(string $text): never
    {
        throw new \Exception($text);
    }
}
