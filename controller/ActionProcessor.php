<?php

declare(strict_types=1);

namespace controller;

use database\Server;
use database\Table;
use dto\ConnectConfig;
use service\UrlMaker;
use service\Validate;

/**
 * Управление запросами. Здесь должны быть централизованы все запросы на изменение данных
 * Это позволит все запросы совершать как через URL, так и через AJAX
 */
class ActionProcessor
{
    // Куда перенаправлять в случае не ajax запроса
    public string $redirect = '';

    /**
     * @return false|void
     * @throws \Exception
     */
    public function __invoke()
    {
        if (POST('ajax')) {
            $action = POST('mode');
        } else {
            $action = GET('action', POST('action'));
        }

        if ($action == null) {
            return false;
        }

        if (!$this->generalActions($action)) {
            if (!$this->databaseActions($action)) {
                return ;
            }
        }

        if (isAjax()) {
            ajaxResultWithMessages();
        } elseif ($this->redirect) {
            header('Location: ' . $this->redirect);
        }
    }

    /**
     * @param string $action
     * @return bool
     */
    public function generalActions(string $action): bool
    {
        global $msc;

        switch ($action) {
            case 'configUpdate':
                $json = json_decode(file_get_contents(MS_CONFIG_FILE));
                $changed = false;
                foreach ($json as $item) {
                    $newValue = POST($item->name);
                    if ($item->type === 'integer' || $item->type === 'boolean') {
                        if (intval($newValue) != intval($item->value)) {
                            $item->value = intval($newValue);
                            $changed = true;
                        }
                    } elseif ($newValue != $item->value) {
                        $item->value = $newValue;
                        $changed = true;
                    }
                }
                if ($changed) {
                    $content = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    if (file_put_contents(MS_CONFIG_FILE, $content)) {
                        $msc->success('Конфиг обновлён');
                    } else {
                        $msc->error('Не удалось записать конфиг в файл');
                    }
                } else {
                    $msc->error('Нечего обновлять');
                }
                break;

            case 'configRestore':
                if (copy(MS_CONFIG_DEFAULT_FILE, MS_CONFIG_FILE)) {
                    $msc->success('Значения по умолчанию восстановлены');
                }
                break;

            case 'connectCheck':
                $config = json_decode($_POST['config'], true);
                $config = $config[$_POST['current']];
                $config = new ConnectConfig(
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['user'],
                    $config['password'],
                    $config['driver'],
                );
                try {
                    $msc->connectPdo($config);
                    $msc->success('Connect success');
                } catch (\PDOException $e) {
                    $msc->error($e->getMessage());
                };
                break;

            case 'connectSave':
            case 'connectOpen':
            $config = json_decode($_POST['config'], true);
                $config = [
                    'current' => $_POST['current'],
                    'config' => $config,
                ];
                $backupFile = MS_DIR_UPLOAD . '/backup_' . date('YmdHis') . '_' . basename(MS_CONNECT_CONFIG_FILE);
                if (file_exists(MS_CONNECT_CONFIG_FILE)) {
                    copy(MS_CONNECT_CONFIG_FILE, $backupFile);
                }
                $content = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $res = file_put_contents(MS_CONNECT_CONFIG_FILE, $content);
                if ($action == 'connectSave') {
                    if ($res) {
                        $msc->success('Конфиг сохранен');
                    } else {
                        $msc->error('Ошибка сохранения конфига');
                    }
                    break;
                }
                $msc->connect();
                if ($msc->connected()) {
                    $msc->success('Connect success!');
                }
                break;
            default:
                return false;
        }
        return true;
    }


    /**
     * Действия с базой данных
     * @param string $action
     * @return bool
     * @throws \Exception
     */
    public function databaseActions(string $action): bool
    {
        global $msc;

        if (!$msc->connected()) {
            return false;
        }

        $db = $this->param('db');
        $tables = $this->param('table');

        if ($db != '') {
            $msc->selectDb($db);
        }

        /**
         * Подгружаем и инициализируем функции для работы с БД
         */
        $dbt = new Table();
        $server = new Server();
        $validate = new Validate();

        // Выполнение запросов
        switch ($action) {
            case 'querysql':
                $sql = POST('sql');
                $type = POST('type');
                if (preg_match('~^\s*(update|delete|insert|drop)~i', $sql)) {
                    $type = 'exec';
                }
                if ($type == 'exec') {
                    $data = $msc->execPdo($sql);
                } elseif ($type == 'pair-value') {
                    $data = $msc->getData($sql, \PDO::FETCH_KEY_PAIR);
                } else {
                    $data = $msc->getData($sql);
                }
                if (is_array($data)) {
                    if (count($data) === 0) {
                        $msc->notice('Пустой результат запроса', $msc->lastSql);
                        ajaxResultWithMessages();
                    } else {
                        ajaxResult($data);
                    }
                } else {
                    $msc->success('Запрос выполнен, затронуто рядов: '.$msc->affectedRows, $msc->lastSql);
                    ajaxResultWithMessages();
                }
                break;

            // операции с таблицами
            // в запросе обязательно должна быть указана БД и таблица

            case 'tableDelete':
                $dbt->tableAction($db, $tables, 'DROP');
                $this->redirect = "?s=tbl_list&db=$db";
                break;

            case 'tableTruncate':
                $dbt->tableAction($db, $tables, 'TRUNCATE');
                $this->redirect = UrlMaker::make('s', 'tbl_data', 'action', '');
                break;

            case 'tableRename':
                if ($dbt->tableAction($db, $tables, 'RENAME', $this->param('newName'))) {
                    $msc->table = $this->param('newName');
                    $this->redirect = UrlMaker::make('table', $msc->table, 'action', '');
                }
                break;

            case 'tableMove':
                if ($validate->queryCheck($db, $tables, $this->param('newName'), $this->param('newDB'))) {
                    if ($dbt->copyTable($db, $tables, true, true, $this->param('newName'), $this->param('newDB'))) {
                        $dbt->tableAction($db, $tables, 'DROP');
                    }
                }
                break;

            // Копирование в другую БД
            case 'tableCopyTo':
                $withData = !$this->param('tableCopyNoData');
                $dbt->copyTable($db, $tables, true, $withData, $this->param('newName'), $this->param('newDB'));
                break;

            // Изменение кодировки
            case 'tableCharset':
                $dbt->tableAction($db, $tables, 'CHARSET', $this->param('charset'));
                break;

            // Изменение опций
            case 'tableOptions':
                if (count($_POST) == 0) {
                    break;
                }
                $table = $tables;
                $ai = intval($this->param('auto_increment'));
                $sql = "ALTER TABLE `$table` AUTO_INCREMENT=$ai";
                if ($msc->execPdo($sql)) {
                    $msc->success('Таблица изменена', $sql);
                } else {
                    $msc->error('Ошибка изменения таблицы', $sql);
                }
                break;

            // Коммент
            case 'tableComment':
                // !!! внимание, некоторые действия должны выполнятся только с POSTa
                // если идёт пустой GET запрос, он всё перетирает!!!
                if (count($_POST) == 0) {
                    break;
                }
                $dbt->tableAction($db, $tables, 'COMMENT', $this->param('comment'));
                break;

            // Найти и заменить
            case 'tableReplace':
                $field = POST('field');
                $search_for = POST('search_for');
                $replace_in = POST('replace_in');
                if ($field && $search_for) {
                    $sql = 'UPDATE `' . $tables . '` SET ' . $field . ' = REPLACE(`' . $field . '`, "' .
                        $search_for . '", "' . $replace_in . '")';
                    if ($msc->execPdo($sql)) {
                        $c = $msc->affectedRows;
                        if ($c > 0) {
                            $msc->success('Таблица изменена, затронуто рядов: ' . $c, $sql);
                        } else {
                            $msc->error('Ничего не найдено и не заменено', $sql);
                        }
                    } else {
                        $msc->error('Ошибка при изменении таблицы', $sql);
                    }
                }
                break;

            // Порядок
            case 'tableOrder':
                if (count($_POST) == 0) {
                    break;
                }
                $dbt->tableAction($db, $tables, 'ORDER', '`' . $this->param('field') . '` ' . $this->param('order'));
                break;

            case 'tableCheck':
                $dbt->tableAction($db, $tables, 'CHECK');
                break;
            case 'tableAnalize':
                $dbt->tableAction($db, $tables, 'ANALYZE');
                break;
            case 'tableRepair':
                $dbt->tableAction($db, $tables, 'REPAIR');
                break;
            case 'tableOptimize':
                $dbt->tableAction($db, $tables, 'OPTIMIZE');
                break;
            case 'tableFlush':
                $dbt->tableAction($db, $tables, 'FLUSH');
                break;

            // массовые действия с таблицами
            case 'delete_all':
            case 'truncate_all':
            case 'copy_all':
                if (!is_array($tables)) {
                    $msc->error('table не массив');
                    break;
                }
                if (!$validate->queryCheck($db)) {
                    break;
                }                ;
                $copyStruct = (POST('copy_struct') != '');
                $copyData = (POST('copy_data') != '');
                foreach ($tables as $table) {
                    if ($action == 'delete_all') {
                        $dbt->tableAction($db, $table, 'DROP');
                    } elseif ($action == 'truncate_all') {
                        $dbt->tableAction($db, $table, 'TRUNCATE');
                    } elseif ($action == 'copy_all') {
                        $dbt->copyTable($db, $table, $copyStruct, $copyData);
                    }
                }
                break;


            // операции с БД

            // удаление баз данных (массово + единично)
            case 'dbDelete':
                if ($this->param('dbMulty')) {
                    $databases = $this->param('databases');
                } else {
                    $databases = [$this->param('dbDelete')];
                }
                if ($databases) {
                    foreach ($databases as $db) {
                        $deletedCurrentDb = $db == $msc->db;
                        $server->databaseAction($db, 'DROP');
                        if ($deletedCurrentDb) {
                            $msc->clearCurrentDatabase();
                        }
                    }
                    $this->redirect = '?s=db_list';
                }
                break;

            case 'dbTruncate':
                $server->databaseTruncate($db);
                break;

            case 'dbHide':
                if ($this->param('act') == 'show') {
                    $msc->execPdo('REPLACE INTO mysqlcenter.db_info (db_name, visible) VALUES("' . $db . '", 1)');
                    $msc->success("База $db открыта");
                } else {
                    $msc->execPdo('REPLACE INTO mysqlcenter.db_info (db_name, visible) VALUES("' . $db . '", 0)');
                    $msc->success("База $db скрыта");
                }
                break;

            case 'dbTablesDelete':
                $server->databaseTruncate($db, true);
                $this->redirect = '?s=tbl_list';
                break;

            case 'dbCreate':
                $server->databaseAction($this->param('dbName'), 'CREATE', $this->param('user'), $this->param('option'));
                break;

            case 'dbCollate':
            case 'dbCharset':
                if ($server->databaseAlterCharset($db, $this->param('charset'), $action == 'dbCharset')) {
                    $msc->success("Успешно выполнено", $msc->lastSql);
                } else {
                    $msc->error("Ошибка при выполнении операции с $db", $msc->lastSqlr);
                }
                break;

            case 'dbAllAction':
                $act = POST('act');
                foreach ($tables as $table) {
                    if (in_array($act, ['analyze', 'check', 'flush', 'repair', 'optimize'])) {
                        $sql = strtoupper($act) . ' TABLE `' . $table . '`';
                        if ($msc->fetchPdo($sql)) {
                            $msc->success('Запрос выполнен', $sql);
                        } else {
                            $msc->error('Ошибка запроса', $sql);
                        }
                    }
                }
                break;


            // массово + единично
            case 'dbRename':
            case 'dbCopy':
                $isMove = ($action == 'dbRename');
                if ($this->param('dbMulty')) {
                    $databases = $this->param('databases');
                    $newName = [];
                    foreach ($databases as $db) {
                        $new = $db . '_copy';
                        if (in_array($new, $databases)) {
                            $new = $db . '_copy' . rand(1, 100);
                        }
                        $newName[] = $new;
                    }
                } else {
                    $databases = [$db];
                    $newName = [$this->param('newName')];
                }
                $data = true;
                $struct = true;
                if (POST('option') != null) {
                    $data = (POST('option') != 'struct');
                    $struct = (POST('option') != 'data');
                }
                if (count($newName) > 0 && count($databases) == count($newName)) {
                    $k = 0;
                    foreach ($databases as $k => $db) {
                        $server->databaseCopy($db, $newName[$k], $isMove, $struct, $data);
                    }
                    if ($isMove || POST('switch') != null) {
                        $msc->db = $newName[$k]; // last
                        //$msc->page = 'db_list';
                    }
                }
                break;

            // операции с рядами

            case 'deleteRows':
            case 'deleteRow':
                $row = $this->param('row');
                if (!$validate->queryCheck($db, $tables, $row)) {
                    break;
                }
                if (!is_array($row)) {
                    $row = [$row];
                }
                if ($dbt->rowDelete($db, $tables, implode(' OR ', $row))) {
                    $msc->success("Рядов удалёно: $msc->affectedRows", $msc->lastSql);
                } else {
                    $msc->error("Ошибка удаления ряда", $msc->lastSql);
                }
                break;

            case 'copyRows':
            case 'copyRow':
                $row = $this->param('row');
                if (!$validate->queryCheck($db, $tables, $row)) {
                    break;
                }
                if (is_array($row)) {
                    $row = implode(' OR ', $row);
                }
                if ($dbt->rowCopy($tables, $row)) {
                    $n = $msc->affectedRows;
                    if ($n > 0) {
                        $msc->success('Добавлено ' . $n . ' рядов', $msc->lastSql);
                    } else {
                        $msc->success('Всё в порядке', $msc->lastSql);
                    }
                } else {
                    $text = 'Ошибка копирования ряда ' . $row;
                    $msc->error($text, $msc->lastSql);
                }
                break;

            // операции с полями

            case 'deleteField':
                if (!$validate->queryCheck($db, $tables, $this->param('field'))) {
                    break;
                }
                $sql = "ALTER TABLE `$tables` DROP " . $this->param('field');
                if ($msc->execPdo($sql, $db)) {
                    $msc->success('Поле удалено', $sql);
                } else {
                    $msc->success('Ошибка удаления поля', $sql);
                }
                break;

            // Удаление множества полей через POST
            case 'fieldsDelete':
                $deleteFields = $this->param('field');
                $fields = Table::getFields($tables);
                // если в таблице осталось только 1 поле, то удаляем таблицу
                if (count($fields) == 1) {
                    $sql = 'DROP TABLE `' . $tables . '`';
                } else {
                    $sql = 'ALTER table `' . $tables . '` DROP `' . implode('`, DROP `', $deleteFields) . '`';
                }
                if ($msc->execPdo($sql)) {
                    $msc->success('Таблица изменена', $sql);
                } else {
                    $msc->error('Ошибка при изменении таблицы', $sql);
                }
                break;

            // операции с ключами

            case 'deleteKey':
                if (!$validate->queryCheck($db, $tables, $this->param('key'), $this->param('field'))) {
                    break;
                }
                if ($this->param('key') == 'PRIMARY') {
                    Table::dropPrimaryKey($tables);
                } else {
                    $sql = "ALTER TABLE `$tables` DROP KEY " . $this->param('key');
                    if ($msc->execPdo($sql)) {
                        $msc->success('Ключ удален', $sql);
                    } else {
                        $msc->error('Ошибка удаления ключа', $sql);
                    }
                }
                break;

            case 'addKey':
                $keyName = POST('keyName');
                $keyDefinition = POST('keyType');
                if (!$validate->queryCheck($db, $tables, $keyName, $keyDefinition)) {
                    break;
                }
                if ($keyName != '') {
                    $keyDefinition .= ' `' . $keyName . '`';
                }
                $keyFields = [];
                foreach ($_POST['field'] as $key => $fieldName) {
                    if ($fieldName == '') {
                        continue;
                    }
                    $fieldSize = $_POST['length'][$key];
                    $keyFields [] = '`' . $fieldName . '`' . ($fieldSize > 0 ? "($fieldSize)" : '');
                }
                $sql = 'ALTER TABLE ' . $tables . ' ADD ' . $keyDefinition . ' (' . implode(',', $keyFields) . ')';
                if ($msc->execPdo($sql)) {
                    $msc->success('Ключ добавлен', $sql);
                } else {
                    $msc->error('Ошибка создания ключа', $sql);
                }
                break;

            // разное

            case 'killProcess':
                $kill = POST('id');
                if (!empty($kill)) {
                    if ($msc->execPdo($sql = 'KILL ' . $kill)) {
                        $msc->success('Успешно удалено');
                    } else {
                        $msc->error('Ошибка остановки', $sql);
                    }
                }
                break;

            // PostgresSQL

            case 'schemaAdd':
                $name = POST('name');
                if (!empty($name)) {
                    if ($msc->execPdo($sql = 'CREATE SCHEMA  ' . $name)) {
                        $msc->success('Схема успешно создана');
                    } else {
                        $msc->error('Ошибка создания схемы', $sql);
                    }
                }
                break;

            default:
                return false;
        }
        return true;
    }


    /**
     * Возвращает параметр запроса
     *
     * @param string $name Имя параметра
     * @return mixed Возвращает null если параметра нет, иначе сам параметр
     */
    private function param(string $name): mixed
    {
        // 1. GET-параметры имеют первичное значение (?db=...)
        if (isset($_GET[$name])) {
            return $_GET[$name];
        }
        // 2. POST-параметры ищем во вторую очередь
        if (isset($_POST[$name])) {
            return $_POST[$name];
        }
        return null;
    }
}
