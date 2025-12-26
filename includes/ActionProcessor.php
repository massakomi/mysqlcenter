<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

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

        if (isajax()) {
            ajaxResultWithMessages();
        } elseif ($this->redirect) {
            header('Location: ' . $this->redirect);
        }
    }

    /**
     * @param $queryMode
     * @return void
     */
    public function generalActions($queryMode): void
    {
        global $msc;

        switch ($queryMode) {

            case 'configUpdate' :
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
                        $msc->addMessage('Конфиг обновлён');
                    } else {
                        $msc->addMessage('Не удалось записать конфиг в файл');
                    }
                    fclose($f);
                } else {
                    $msc->addMessage('Нечего обновлять');
                }
                break;

            case 'configRestore' :
                if (copy('docs/config_default.txt', MS_CONFIG_FILE)) {
                    $msc->addMessage('Значения по умолчанию восстановлены');
                }
                break;

            case 'connectSave' :
            case 'connectCheck' :
                $config = json_decode($_POST['config'], true);
                $config = [
                    'current' => $_POST['current'],
                    'config' => $config,
                ];
                $res = file_put_contents(MS_CONNECT_CONFIG_FILE, json_encode($config));
                if ($queryMode == 'connectSave') {
                    if ($res) {
                        $msc->addMessage('Конфиг сохранен');
                    } else {
                        $msc->addMessage('Ошибка сохранения конфига', '', MS_MSG_ERROR);
                    }
                    break;
                }
                $msc->connect();
                $msc->addMessage('Connect success!');
                break;
        }

    }


    /**
     * Действия с базой данных
     * @param $queryMode
     * @return bool|void
     */
    public function databaseActions($queryMode)
    {
        global $msc, $umaker;

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
        $dbm = new DatabaseManager();
        $dbt = new DatabaseTable();
        $dbr = new DatabaseRow();
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

            case 'tableDelete'    :
                $dbt->tableAction($db, $tbl, 'DROP');
                $this->redirect = "?s=tbl_list&db=$db";
                break;

            case 'tableTruncate'  :
                $dbt->tableAction($db, $tbl, 'TRUNCATE');
                $this->redirect = $umaker->make('s', 'tbl_data', 'action', '');
                break;

            case 'tableRename':
                if ($dbt->tableAction($db, $tbl, 'RENAME', $this->param('newName'))) {
                    $msc->table = $this->param('newName');
                    $this->redirect = $umaker->make('table', $msc->table, 'action', '');
                }
                break;

            case 'tableMove':
                $validate->queryCheck($db, $tbl, $this->param('newName'), $this->param('newDB'));
                if ($dbt->copyTable($db, $tbl, true, true, $this->param('newName'), $this->param('newDB'))) {
                    $dbt->tableAction($db, $tbl, 'DROP');
                }
                break;

            // Копирование в другую БД
            case 'tableCopyTo';
                $withData = !$this->param('tableCopyNoData');
                $dbt->copyTable($db, $tbl, true, $withData, $this->param('newName'), $this->param('newDB'));
                break;

            // Изменение кодировки
            case 'tableCharset';
                $dbt->tableAction($db, $tbl, 'CHARSET', $this->param('charset'));
                break;

            // Изменение опций
            case 'tableOptions';
                if (count($_POST) == 0) {
                    break;
                }
                $table = $tbl;
                $ai = intval($this->param('auto_increment'));
                $pk = intval($this->param('pack_keys'));
                $cs = intval($this->param('checksum'));
                $dkv = intval($this->param('delay_key_write'));
                $sql = "ALTER TABLE `$table` PACK_KEYS = $pk CHECKSUM = $cs DELAY_KEY_WRITE = $dkv AUTO_INCREMENT = $ai";
                if ($msc->execPdo($sql)) {
                    return $msc->addMessage('Таблица изменена', $sql, MS_MSG_SUCCESS);
                } else {
                    return $msc->addMessage('Ошибка изменения таблицы', $sql, MS_MSG_FAULT);
                }
                break;

            // Коммент
            case 'tableComment';
                // !!! внимание, некоторые действия должны выполнятся только с POSTa
                // если идёт пустой GET запрос, он всё перетирает!!!
                if (count($_POST) == 0) {
                    break;
                }
                $dbt->tableAction($db, $tbl, 'COMMENT', $this->param('comment'));
                break;

            // Найти и заменить
            case 'tableReplace';
                $field = POST('field');
                $search_for = POST('search_for');
                $replace_in = POST('replace_in');
                if ($field && $search_for) {
                    $sql = 'UPDATE `'.$tbl.'` SET '.$field.' = REPLACE(`'.$field.'`, "'.$search_for.'", "'.$replace_in.'")';
                    if ($msc->execPdo($sql)) {
                        $c = $msc->affectedRows;
                        if ($c > 0) {
                            $msc->addMessage('Таблица изменена, затронуто рядов: '.$c, $sql, MS_MSG_SUCCESS);
                        } else {
                            $msc->addMessage('Ничего не найдено и не заменено', $sql, MS_MSG_NOTICE);
                        }
                    } else {
                        $msc->addMessage('Ошибка при изменении таблицы', $sql, MS_MSG_FAULT);
                    }
                }
                break;

            // Порядок
            case 'tableOrder';
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
            case 'delete_all' :
            case 'truncate_all' :
            case 'copy_all' :
                $a = $tbl;
                if (!is_array($a)) {
                    $msc->addMessage('table не массив',  '', MS_MSG_ERROR);
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
                    } else if ($queryMode == 'truncate_all') {
                        $dbt->tableAction($db, $t, 'TRUNCATE');
                    } else if ($queryMode == 'copy_all') {
                        $dbt->copyTable($db, $t, $cs, $cd);
                    }
                }
                break;


            // операции с БД

            // удаление баз данных (массово + единично)
            case 'dbDelete'       :
                if ($this->param('dbMulty')) {
                    $databases = $this->param('databases');
                } else {
                    $databases = array($db);
                }
                if ($databases) {
                    foreach ($databases as $db) {
                        $dbm->DatabaseAction($db, 'DROP');
                    }
                    $msc->clearCurrentDatabase();
                    $msc->page = 'db_list';
                }
                break;

            case 'dbTruncate'     :
                $dbm->DatabaseTruncate($db);
                break;

            case 'dbHide'     :
                if ($this->param('act') == 'show') {
                    $msc->execPdo('REPLACE INTO mysqlcenter.db_info (db_name, visible) VALUES("' . $db . '", 1)');
                    $msc->addMessage("База $db открыта");
                } else {
                    $msc->execPdo('REPLACE INTO mysqlcenter.db_info (db_name, visible) VALUES("' . $db . '", 0)');
                    $msc->addMessage("База $db скрыта");
                }
                break;

            case 'dbTablesDelete' :
                $dbm->DatabaseTruncate($db, true);
                break;

            case 'dbCreate'       :
                if ($dbm->DatabaseAction($this->param('dbName'), 'CREATE')) {
                    $msc->db = $this->param('dbName');
                    $msc->selectDb($msc->db);
                }
                break;

            case 'dbCollate'       :
            case 'dbCharset'       :
                if ($dbm->DatabaseAlterCharset($db, $this->param('charset'), $queryMode == 'dbCharset')) {
                    $msc->addMessage("Успешно выполнено", $msc->lastSql, MS_MSG_SUCCESS);
                } else {
                    $msc->addMessage("Ошибка при выполнении операции с $db", $msc->lastSql, MS_MSG_FAULT, $msc->error);
                }
                break;

            case 'dbAllAction'       :
                $tables = DatabaseTable::getTables();
                $action = POST('act');
                foreach ($tables as $table) {
                    if ($action === 'drop-query') {
                        $sql = 'DROP TABLE `' . $table . '`;';
                        $msc->addMessage($sql, '', MS_MSG_SUCCESS);
                    }

                    if (in_array($action, ['analyze', 'check', 'flush', 'repair', 'optimize'])) {
                        $sql = strtoupper($action) . ' TABLE `' . $table . '`';
                        if ($msc->execPdo($sql)) {
                            $msc->addMessage('Запрос выполнен', $sql, MS_MSG_SUCCESS);
                        } else {
                            $msc->addMessage('Ошибка запроса', $sql, MS_MSG_FAULT);
                        }
                    }
                }
                break;


            // массово + единично
            case 'dbRename'       :
            case 'dbCopy'         :
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
                    $databases = array($db);
                    $newName = array($this->param('newName'));
                }
                $data = true;
                $struct = true;
                if (POST('option') != null) {
                    $data = (POST('option') != 'struct');
                    $struct = (POST('option') != 'data');
                }
                if (count($newName) > 0 && count($databases) == count($newName)) {
                    foreach ($databases as $k => $db) {
                        $dbm->DatabaseCopy($db, $newName[$k], $isMove, $struct, $data);
                    }
                    if ($isMove || POST('switch') != null) {
                        $msc->db = $newName[$k]; // last
                        //$msc->page = 'db_list';
                    }
                }
                break;

            // операции с рядами

            case 'deleteRows' :
            case 'deleteRow':
                $row = $this->param('row');
                $validate->queryCheck($db, $tbl, $row);
                if (!is_array($row)) {
                    $row = [$row];
                }
                if ($dbr->rowDelete($db, $tbl, implode(' OR ', $row), count($row))) {
                    return $msc->addMessage("Ряд $row удалён", $msc->lastSql, MS_MSG_SUCCESS);
                } else {
                    return $msc->addMessage("Ошибка удаления ряда $row", $msc->lastSql, MS_MSG_FAULT, $msc->error);
                }
                break;

            case 'copyRows' :
            case 'copyRow':
                $row = $this->param('row');
                $validate->queryCheck($db, $tbl, $row);
                if (is_array($row)) {
                    $row = implode(' OR ', $row);
                }
                if ($dbr->rowCopy($tbl, $row)) {
                    $n = $msc->affectedRows;
                    if ($n > 0) {
                        return $msc->addMessage('Добавлено ' . $n . ' рядов', $msc->lastSql, MS_MSG_SUCCESS);
                    } else {
                        return $msc->addMessage('Всё в порядке', $msc->lastSql, MS_MSG_SUCCESS);
                    }
                } else {
                    return $msc->addMessage('Ошибка копирования ряда ' . $row, $msc->lastSql, MS_MSG_FAULT, $msc->error);
                }
                break;

            case 'rowsAdd':
                processRowsEdit(1);
                break;

            case 'rowsEdit':
                processRowsEdit(0);
                break;

            // операции с полями

            case 'deleteField' :
                $validate->queryCheck($db, $tbl, $this->param('field'));
                $sql = "ALTER TABLE `$tbl` DROP " . $this->param('field');
                if ($msc->execPdo($sql, $db)) {
                    $msc->addMessage('Поле удалено', $sql, MS_MSG_SUCCESS);
                } else {
                    $msc->addMessage('Ошибка удаления поля', $sql, MS_MSG_FAULT, $msc->error);
                }
                break;

            // Удаление множества полей через POST
            case 'fieldsDelete' :
                $deleteFields = $this->param('field');
                $fields = DatabaseTable::getFields($tbl);
                // если в таблице осталось только 1 поле, то удаляем таблицу
                if (count($fields) == 1) {
                    $sql = 'DROP TABLE `' . $tbl . '`';
                } else {
                    $sql = 'ALTER table `' . $tbl . '` DROP `' . implode('`, DROP `', $deleteFields) . '`';
                }
                if ($msc->execPdo($sql)) {
                    $msc->addMessage('Таблица изменена', $sql, MS_MSG_SUCCESS);
                } else {
                    $msc->addMessage('Ошибка при изменении таблицы', $sql, MS_MSG_FAULT, $msc->error);
                }
                break;

            // операции с ключами

            case 'deleteKey' :
                $validate->queryCheck($db, $tbl, $this->param('key'), $this->param('field'));
                if ($this->param('key') == 'PRIMARY') {
                    DatabaseTable::dropPrimaryKey($tbl);
                } else {
                    $sql = "ALTER TABLE `$tbl` DROP KEY " . $this->param('key');
                    if ($msc->execPdo($sql)) {
                        $msc->addMessage('Ключ удален', $sql, MS_MSG_SUCCESS);
                    } else {
                        $msc->addMessage('Ошибка удаления ключа', $sql, MS_MSG_FAULT, $msc->error);
                    }
                }
                break;

            case 'addKey' :
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
                    $msc->addMessage('Ключ добавлен', $sql, MS_MSG_SUCCESS);
                } else {
                    $msc->addMessage('Ошибка создания ключа', $sql, MS_MSG_FAULT);
                }
                break;

            // операции с пользователем

            case 'userAdd' :
                Server::userAdd();
                break;

            // разное

            case 'killProcess' :
                $kill = POST('id');
                if (!empty($kill)) {
                    if ($msc->execPdo($sql = 'KILL ' . $kill)) {
                        $msc->addMessage('Успешно удалено');
                    } else {
                        $msc->addMessage('Ошибка остановки', $sql, MS_MSG_ERROR, $msc->error);
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

