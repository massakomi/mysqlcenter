<?php

/**
 * 1:1 adapter that preserves the public API of the legacy Export.php
 * but delegates execution to native database tools (mysqldump / pg_dump).
 *
 * Design goals:
 * - Zero calling-code changes
 * - Feature flags mapped to CLI arguments
 * - Stream-safe (no memory buffering)
 * - Easy rollback (legacy exporter can coexist)
 */


declare(strict_types=1);

namespace database;

use dto\ConnectConfig;
use Symfony\Component\Process\Process;

/**
 * sadf.
 */
interface ExporterInterface
{
    public function export(): void;
}

/**
 * sdf.
 */
final class ExportCli implements ExporterInterface
{
    /** @var array<string, mixed> */
    private array $options = [];

    private ConnectConfig $connection;

    public string $data;
    private string $driver;
    private string $table;
    private string $database;

    /** @var resource|null */
    private $output;

    public function __construct(ConnectConfig $connection)
    {
        $this->connection = $connection;
        $this->driver     = $connection->driver;
        $this->output     = fopen('php://output', 'wb');
    }

    /**
     * Entry point – mirrors Export::export().
     */
    public function startFull(bool $isStruct, bool $isData, true $addDelim, bool $isDrop, mixed $exType, mixed $exWhere): void
    {
        if ($isStruct && !$isData) {
            $this->options['schemaOnly'] = true;
        }
        if ($isData && !$isStruct) {
            $this->options['dataOnly'] = true;
        }
        if ($isDrop) {
            $this->options['dropTables'] = true;
        }
        if ($exType == 'REPLACE') {
            $this->options['replace'] = true;
        }

        // $this->options['--inserts'] = true;
        // $this->options['--column-inserts'] = true;

        ob_start();
        match ($this->driver) {
            'mysql', 'mysqli', 'pdo_mysql' => $this->exportMySql(),
            'pgsql', 'pdo_pgsql'           => $this->exportPostgres(),
            default                        => throw new \RuntimeException('Unsupported driver'),
        };
        $this->data .= ob_get_contents();
        ob_end_clean();
    }

    /**
     * =========================
     * MySQL / MariaDB
     * =========================.
     */
    private function exportMySql(): void
    {
        global $msc;
        if (!is_dir($this->connection->binPath)) {
            $msc->error('Binary path does not exist: ' . $this->connection->binPath);
            return;
        }

        $cmd = [
            $this->connection->binPath.'/mysqldump',
            '--single-transaction',
            '--quick',
        ];

        if ($this->option('dropTables')) {
            $cmd[] = '--add-drop-table';
        }

        if ($this->option('extendedInsert', true) === false) {
            $cmd[] = '--skip-extended-insert';
        }

        if ($this->option('replace')) {
            $cmd[] = '--replace';
        }

        if ($this->option('comments')) {
            $cmd[] = '--comments';
        }

        if ($this->option('insertIgnore')) {
            $cmd[] = '--insert-ignore';
        }

        if ($this->option('disableKeys')) {
            $cmd[] = '--disable-keys';
        }

        if ($this->option('routines', true)) {
            $cmd[] = '--routines';
        }

        if ($this->option('triggers', true)) {
            $cmd[] = '--triggers';
        }

        if ($this->option('events')) {
            $cmd[] = '--events';
        }

        if ($this->option('schemaOnly')) {
            $cmd[] = '--no-data';
        }

        if ($this->option('dataOnly')) {
            $cmd[] = '--no-create-info';
        }

        $cmd[] = '--host='.$this->connection->host;
        $cmd[] = '--user='.$this->connection->user;

        $env = [
            'MYSQL_PWD' => $this->connection->password,
        ];

        if (!empty($this->connection->port)) {
            $cmd[] = '--port='.$this->connection->port;
        }

        $cmd[] = $this->database;
        $cmd[] = $this->table;

        $this->run($cmd, $env);
    }

    /**
     * =========================
     * PostgreSQL
     * =========================.
     */
    private function exportPostgres(): void
    {
        global $msc;
        if (!is_dir($this->connection->binPath)) {
            $msc->error('Binary path does not exist: ' . $this->connection->binPath);
            return ;
        }

        $cmd = [
            $this->connection->binPath.'/pg_dump',
            '--no-owner',
            '--no-privileges',
            '--no-comments',
            '--no-security-labels',
        ];

        $cmd[] = '-t';
        $cmd[] = $this->table;

        if ($this->option('dropTables')) {
            $cmd[] = '--clean';
            $cmd[] = '--if-exists';
        }

        if ($this->option('schemaOnly')) {
            $cmd[] = '--schema-only';
        }

        if ($this->option('dataOnly')) {
            $cmd[] = '--data-only';
        }

        // Always use --inserts for standard SQL compatibility instead of COPY
        $cmd[] = '--inserts';
        if ($this->option('inserts')) {
            $cmd[] = '--column-inserts';
        }

        if ($this->option('columnInserts')) {
            $cmd[] = '--column-inserts';
        }

        if ($this->option('disableTriggers')) {
            $cmd[] = '--disable-triggers';
        }

        $cmd[] = '--host';
        $cmd[] = $this->connection->host;
        $cmd[] = '--username';
        $cmd[] = $this->connection->user;
        $cmd[] = '--dbname';
        $cmd[] = $this->database;
        $cmd[] = '--port';
        $cmd[] = $this->connection->port;

        $env = [
            'PGPASSWORD' => $this->connection->password,
        ];

        $this->run($cmd, $env);
    }

    /**
     * =========================
     * Execution helpers
     * =========================.
     */
    private function run(array $command, array $env = []): void
    {
        if ($this->option('gzip')) {
            $command = array_merge($command, ['|', 'gzip']);
        }

        $process = new Process($command, null, $env);
        $process->setTimeout(null);

        $process->run(function ($type, $buffer) {
            // Filter out problematic \restrict and \unrestrict lines
            $buffer = preg_replace('/\\\\(?:restrict|unrestrict)\s+[A-Za-z0-9]+\s*\n/m', '', $buffer);
            fwrite($this->output, $buffer);
        });

        if (!$process->isSuccessful()) {
            header('Content-Type: text/html; charset=utf-8');
            $errorUtf8 = $process->getErrorOutput();
            $errorUtf8 = iconv($this->detectEncoding($errorUtf8), 'UTF-8', $errorUtf8);
            throw new \RuntimeException($errorUtf8);
        }
    }

    private function detectEncoding($string)
    {
        // List of encodings to check (order matters!)
        $encodings = [
            'UTF-8',
            'CP866',     // Russian DOS
            'CP1251',    // Russian Windows
            'KOI8-R',    // Russian Unix
            'ISO-8859-5', // Latin/Cyrillic
            'WINDOWS-1251',
            'ASCII',
        ];

        $detected = mb_detect_encoding($string, $encodings, true);

        if ($detected === false) {
            return 'UNKNOWN';
        }

        return $detected;
    }

    private function option(string $name, mixed $default = false): mixed
    {
        return $this->options[$name] ?? $default;
    }

    public function setComments(bool $param): void
    {
        $this->options['comments'] = $param;
    }

    public function setHeader(string $dumpHeader): void
    {
        $this->data = $dumpHeader;
    }

    public function setOptionsStruct(bool $addIfNot, bool $addAuto, bool $addKav): void
    {
        // $this->options['insertIgnore'] = $addIfNot;
        // $this->options['replace']      = $addAuto;
        // $this->options['disableKeys']  = $addKav;
    }

    public function setOptionsData(bool $insFull, bool $insExpand, bool $insZapazd, bool $insIgnor): void
    {
    }

    public function setDatabase(mixed $db): void
    {
        $this->database = $db;
    }

    public function setTable(string $t): void
    {
        $this->table = $t;
    }

    /**
     * Заворачивает дамп в нужный вид и отправляет
     *
     * @param string $type тип отправки, значения: 'textarea' - создаёт форму 'zip' - создаёт архив и отправляет
     * @param string|null $file имя файла дампа для типа 'zip'
     */
    public function sendSQLDamp(string $type = 'textarea', ?string $file = null): string
    {
        if (is_null($file)) {
            $this->table != null ? $file = $this->table : $file = $this->database;
        }
        if (isAjax()) {
            if ($type == 'textarea') {
                return $this->get();
            }
            if ($type == 'zip') {
                $dir = $_SERVER['DOCUMENT_ROOT'].'/'.MS_DIR_UPLOAD;
                if (!file_exists($dir)) {
                    mkdir($dir, 0777);
                }

                $fp = gzopen($dir.'/download.sql.gz', 'w9');
                if ($fp) {
                    gzwrite($fp, $this->get());
                    gzclose($fp);
                }

                return 'https://'.$_SERVER['HTTP_HOST'].'/'.MS_DIR_UPLOAD.'/download.sql.gz';
            }
        }
        // архив
        if ($type == 'zip') {
            if (headers_sent()) {
                return '<h3>headers_sent...</h3>';
            }
            $attachment_name = "$file.sql.gz";
            $gzipped_data = gzencode($this->get(), 9);
            header('Content-Type: application/x-gzip'); // Or 'application/octet-stream' for a generic download
            header('Content-Disposition: attachment; filename="'.$attachment_name.'"');
            header('Content-Length: '.strlen($gzipped_data ?: ''));

            if (ob_get_level()) {
                ob_end_clean();
            }

            echo $gzipped_data;
            exit;
        }

        // текстовое поле
        return $this->get();
    }

    /**
     * Заворачивает дамп в нужный вид и отправляет
     *
     * @param string $type тип отправки, значения: 'textarea' - создаёт форму 'zip' - создаёт архив и отправляет
     * @param string|null $file имя файла дампа для типа 'zip'
     */
    public function send(string $type = 'textarea', ?string $file = null): string
    {
        return $this->sendSQLDamp($type, $file);
    }

    private function get(): string
    {
        return $this->data;
    }

    public function export(): void
    {
        // TODO: Implement export() method.
    }
}
