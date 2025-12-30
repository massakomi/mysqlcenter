<?php

use database\Server;

/**
 * Управление запросами. Здесь должны быть централизованы все запросы на изменение данных
 * Это позволит все запросы совершать как через URL, так и через AJAX
 */
class ActionProcessor
{
    // Куда редиректить в случае не ajax запроса
    public string $redirect = '';

    public function __construct()
    {
        if (POST('ajax')) {
            $queryMode = POST('mode');
        } else {
            $queryMode = GET('action') != null ? GET('action') : POST('action');
        }

        if ($queryMode == null) {
            return false;
        }

        $this->generalActions($queryMode);
        $this->databaseActions($queryMode);

        if (isAjax()) {
            ajaxResultWithMessages();
        } elseif ($this->redirect) {
            header('Location: ' . $this->redirect);
        }
    }

    /**
     * @param string $queryMode
     * @return void
     */
    public function generalActions(string $queryMode): void
    {
        global $msc;

        switch ($queryMode) {
            case 'configUpdate':
                $data = file(MS_CONFIG_FILE);
                $newFileContent = [];
                $changed = false;
                foreach ($data as $k => $line) {
                    if (empty($line) || substr_count($line, '|') < 3) {
                        continue;
                    }
                    list($name, $title, $value, $type) = explode('|', trim($line));
                    if ($type == 'boolean') {
                        if (intval(POST($name)) != intval($value)) {
                            $value = intval(POST($name));
                            $changed = true;
                        }
                    } elseif (isset($_POST[$name]) && POST($name) != $value) {
                        $value = POST($name);
                        $changed = true;
                    }
                    $newFileContent [] = "$name|$title|$value|$type";
                }
                if ($changed) {
                    $f = fopen(MS_CONFIG_FILE, 'w+');
                    if (fwrite($f, implode("\n", $newFileContent))) {
                        $msc->success('Конфиг обновлён');
                    } else {
                        $msc->error('Не удалось записать конфиг в файл');
                    }
                    fclose($f);
                } else {
                    $msc->error('Нечего обновлять');
                }
                break;

            case 'configRestore':
                if (copy(MS_CONFIG_DEFAULT_FILE, MS_CONFIG_FILE)) {
                    $msc->success('Значения по умолчанию восстановлены');
                }
                break;

            case 'connectSave':
            case 'connectCheck':
                $config = json_decode($_POST['config'], true);
                $config = [
                    'current' => $_POST['current'],
                    'config' => $config,
                ];
                $backupFile = MS_DIR_UPLOAD . '/backup_'.date('YmdHis').'_' . basename(MS_CONNECT_CONFIG_FILE);
                if (file_exists(MS_CONNECT_CONFIG_FILE)) {
                    copy(MS_CONNECT_CONFIG_FILE, $backupFile);
                }
                $res = file_put_contents(MS_CONNECT_CONFIG_FILE, json_encode($config));
                if ($queryMode == 'connectSave') {
                    if ($res) {
                        $msc->success('Конфиг сохранен');
                    } else {
                        $msc->error('Ошибка сохранения конфига');
                    }
                    break;
                }
                $msc->connect();
                $msc->success('Connect success!');
                break;
        }
    }


    /**
     * Действия с базой данных
     * @param string $queryMode
     * @return bool|void
     * @throws Exception
     */
    public function databaseActions(string $queryMode)
    {
        global $msc;

        if (!$msc->connected()) {
            return false;
        }

        $db = $this->param('db');
        $tbl = $this->param('table');

        if ($db != '') {
            $msc->selectDb($db);
        }

        /**
         * Подгружаем и инициализируем функции для работы с БД
         */
        $dbt = new DatabaseTable();
        $server = new Server();
        $validate = new Validate();

        // Выполнение запросов
        switch ($queryMode) {
            case 'querysql':
                if ($_POST['type'] == 'pair-value') {
                    $data = $msc->getData($_POST['sql'], PDO::FETCH_KEY_PAIR);
                } else {
                    $data = $msc->getData($_POST['sql']);
                }
                exit(json_encode($data));

            // операции с таблицами
            // в запросе обязательно должна быть указана БД и таблица

            case 'tableDelete':
                $dbt->tableAction($db, $tbl, 'DROP');
                $this->redirect = "?s=tbl_list&db=$db";
                break;

            case 'tableTruncate':
                $dbt->tableAction($db, $tbl, 'TRUNCATE');
                $this->redirect = \UrlMaker::make('s', 'tbl_data', 'action', '');
                break;

            case 'tableRename':
                if ($dbt->tableAction($db, $tbl, 'RENAME', $this->param('newName'))) {
                    $msc->table = $this->param('newName');
                    $this->redirect = \UrlMaker::make('table', $msc->table, 'action', '');
                }
                break;

            case 'tableMove':
                $validate->queryCheck($db, $tbl, $this->param('newName'), $this->param('newDB'));
                if ($dbt->copyTable($db, $tbl, true, true, $this->param('newName'), $this->param('newDB'))) {
                    $dbt->tableAction($db, $tbl, 'DROP');
                }
                break;

            // Копирование в другую БД
            case 'tableCopyTo':
                $withData = !$this->param('tableCopyNoData');
                $dbt->copyTable($db, $tbl, true, $withData, $this->param('newName'), $this->param('newDB'));
                break;

            // Изменение кодировки
            case 'tableCharset':
                $dbt->tableAction($db, $tbl, 'CHARSET', $this->param('charset'));
                break;

            // Изменение опций
            case 'tableOptions':
                if (count($_POST) == 0) {
                    break;
                }
                $table = $tbl;
                $ai = intval($this->param('auto_increment'));
                $sql = "ALTER TABLE `$table` AUTO_INCREMENT=$ai";
                if ($msc->execPdo($sql)) {
                    return $msc->success('Таблица изменена', $sql);
                } else {
                    return $msc->error('Ошибка изменения таблицы', $sql);
                }
                break;

            // Коммент
            case 'tableComment':
                // !!! внимание, некоторые действия должны выполнятся только с POSTa
                // если идёт пустой GET запрос, он всё перетирает!!!
                if (count($_POST) == 0) {
                    break;
                }
                $dbt->tableAction($db, $tbl, 'COMMENT', $this->param('comment'));
                break;

            // Найти и заменить
            case 'tableReplace':
                $field = POST('field');
                $search_for = POST('search_for');
                $replace_in = POST('replace_in');
                if ($field && $search_for) {
                    $sql = 'UPDATE `' . $tbl . '` SET ' . $field . ' = REPLACE(`' . $field . '`, "' .
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
                $dbt->tableAction($db, $tbl, 'ORDER', '`' . $this->param('field') . '` ' . $this->param('order'));
                break;

            case 'tableCheck':
                $dbt->tableAction($db, $tbl, 'CHECK');
                break;
            case 'tableAnalize':
                $dbt->tableAction($db, $tbl, 'ANALYZE');
                break;
            case 'tableRepair':
                $dbt->tableAction($db, $tbl, 'REPAIR');
                break;
            case 'tableOptimize':
                $dbt->tableAction($db, $tbl, 'OPTIMIZE');
                break;
            case 'tableFlush':
                $dbt->tableAction($db, $tbl, 'FLUSH');
                break;

            // массовые действия с таблицами
            case 'delete_all':
            case 'truncate_all':
            case 'copy_all':
                $a = $tbl;
                if (!is_array($a)) {
                    $msc->error('table не массив');
                    break;
                }
                if ($a == false) {
                    break;
                }
                $validate->queryCheck($db);
                $cs = (POST('copy_struct') != '');
                $cd = (POST('copy_data') != '');
                foreach ($a as $t) {
                    if ($queryMode == 'delete_all') {
                        $dbt->tableAction($db, $t, 'DROP');
                    } elseif ($queryMode == 'truncate_all') {
                        $dbt->tableAction($db, $t, 'TRUNCATE');
                    } elseif ($queryMode == 'copy_all') {
                        $dbt->copyTable($db, $t, $cs, $cd);
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
                        $server->databaseAction($db, 'DROP');
                        if ($db == $msc->db) {
                            $msc->clearCurrentDatabase();
                        }
                    }
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
                break;

            case 'dbCreate':
                if ($server->databaseAction($this->param('dbName'), 'CREATE')) {
                    $msc->db = $this->param('dbName');
                    $msc->selectDb($msc->db);
                }
                break;

            case 'dbCollate':
            case 'dbCharset':
                if ($server->databaseAlterCharset($db, $this->param('charset'), $queryMode == 'dbCharset')) {
                    $msc->success("Успешно выполнено", $msc->lastSql);
                } else {
                    $msc->error("Ошибка при выполнении операции с $db", $msc->lastSqlr);
                }
                break;

            case 'dbAllAction':
                $tables = DatabaseTable::getTables();
                $action = POST('act');
                foreach ($tables as $table) {
                    if ($action === 'drop-query') {
                        $sql = 'DROP TABLE `' . $table . '`;';
                        $msc->success($sql);
                    }

                    if (in_array($action, ['analyze', 'check', 'flush', 'repair', 'optimize'])) {
                        $sql = strtoupper($action) . ' TABLE `' . $table . '`';
                        if ($msc->execPdo($sql)) {
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
                $isMove = ($queryMode == 'dbRename');
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
                $validate->queryCheck($db, $tbl, $row);
                if (!is_array($row)) {
                    $row = [$row];
                }
                if ($dbt->rowDelete($db, $tbl, implode(' OR ', $row))) {
                    return $msc->success("Ряд $row удалён", $msc->lastSql);
                } else {
                    return $msc->error("Ошибка удаления ряда $row", $msc->lastSql);
                }
                break;

            case 'copyRows':
            case 'copyRow':
                $row = $this->param('row');
                $validate->queryCheck($db, $tbl, $row);
                if (is_array($row)) {
                    $row = implode(' OR ', $row);
                }
                if ($dbt->rowCopy($tbl, $row)) {
                    $n = $msc->affectedRows;
                    if ($n > 0) {
                        return $msc->success('Добавлено ' . $n . ' рядов', $msc->lastSql);
                    } else {
                        return $msc->success('Всё в порядке', $msc->lastSql);
                    }
                } else {
                    $text = 'Ошибка копирования ряда ' . $row;
                    return $msc->error($text, $msc->lastSql);
                }
                break;

            // операции с полями

            case 'deleteField':
                $validate->queryCheck($db, $tbl, $this->param('field'));
                $sql = "ALTER TABLE `$tbl` DROP " . $this->param('field');
                if ($msc->execPdo($sql, $db)) {
                    $msc->success('Поле удалено', $sql);
                } else {
                    $msc->success('Ошибка удаления поля', $sql);
                }
                break;

            // Удаление множества полей через POST
            case 'fieldsDelete':
                $deleteFields = $this->param('field');
                $fields = DatabaseTable::getFields($tbl);
                // если в таблице осталось только 1 поле, то удаляем таблицу
                if (count($fields) == 1) {
                    $sql = 'DROP TABLE `' . $tbl . '`';
                } else {
                    $sql = 'ALTER table `' . $tbl . '` DROP `' . implode('`, DROP `', $deleteFields) . '`';
                }
                if ($msc->execPdo($sql)) {
                    $msc->success('Таблица изменена', $sql);
                } else {
                    $msc->error('Ошибка при изменении таблицы', $sql);
                }
                break;

            // операции с ключами

            case 'deleteKey':
                $validate->queryCheck($db, $tbl, $this->param('key'), $this->param('field'));
                if ($this->param('key') == 'PRIMARY') {
                    DatabaseTable::dropPrimaryKey($tbl);
                } else {
                    $sql = "ALTER TABLE `$tbl` DROP KEY " . $this->param('key');
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
                $validate->queryCheck($db, $tbl, $keyName, $keyDefinition);
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
                $sql = 'ALTER TABLE ' . $tbl . ' ADD ' . $keyDefinition . ' (' . implode(',', $keyFields) . ')';
                if ($msc->execPdo($sql)) {
                    $msc->success('Ключ добавлен', $sql);
                } else {
                    $msc->error('Ошибка создания ключа', $sql);
                }
                break;

            // операции с пользователем

            case 'userAdd':
                Server::userAdd();
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

            default:
                return false;
        }
    }


    /**
     * Возвращет параметр запроса
     *
     * @param string  Имя параметра
     * @return mixed  Возвращает false если парметра нет, иначе сам параметр
     */
    public function param($name)
    {
        // 1. GET-параметры имеют первичное значение (?db=...)
        if (isset($_GET[$name])) {
            return $_GET[$name];
        }
        // 2. POST-параметры ищем во вторую очередь
        if (isset($_POST[$name])) {
            return $_POST[$name];
        }
        return false;
    }
}
