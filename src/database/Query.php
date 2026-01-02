<?php

declare(strict_types=1);

namespace database;

use dto\ConnectConfig;
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
     * @param FetchType $mode
     * @param string $sql
     * @return bool|int|\PDOStatement|null
     * @throws \Exception
     */
    private function queryPdo(FetchType $mode, string $sql): bool|int|\PDOStatement|null
    {
        global $pdo;
        if (!$pdo) {
            echo '<pre>';
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
                echo $sql;
                echo '<hr />';
                throw new \Exception($this->error);
            }
        }
        if ($this->logEnabled) {
            $this->loqQuery($sql, $result);
        }
        return $result === false ? null : $result;
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
        return $this->fetchPdo($sql)?->fetchAll($type) ?? [];
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
     * Закрывать соединение не нужно, оно автоматически закрывается при завершении скрипта или при обнулении
     * переменной $pdo
     * @param ConnectConfig $config
     * @param string|null $database опционально другая бд для подключения
     * @return void
     */
    public function connectPdo(ConnectConfig $config, ?string $database = null): void
    {
        global $pdo;
        $options = [
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8', collation_connection=" . MS_COLLATION .
                ', character_set_server=' . MS_CHARACTER_SET . ', sql_mode=""'
        ];
        $database = $database ?: $config->database;
        $dsn = $config->driver . ':host=' . $config->host . ';port=' . $config->port . ';dbname=' . $database;
        $pdo = new \PDO($dsn, $config->user, $config->password, $options);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Перехватывает запрос и сохраняет его в файле
     *
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
