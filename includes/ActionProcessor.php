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

    /**
     * Конструктор
     * @param boolean True, если запускается из ajax-скрипта ajax.php
     */
    public function __construct($isAjax = false)
    {
        global $msc;

        if ($isAjax) {
            //$this->params  = $_POST;
            $queryMode = POST('mode');
        } else {
            $queryMode = GET('action') != null ? GET('action') : POST('action');
        }

        if ($queryMode == null) {
            return false;
        }

        $db = $this->param('db');
        $tbl = $this->param('table');

        // Если указана БД, сразу её выбираем. Но дальше в запросах, всё равно проверяем,
        // может БД требуется, но пустая, значит ошибка
        // $db          - та, с которой производятся действия
        // $msc->getCurrentDatabase() - которая отображается (она берётся из GET>куки>сессии>конфига)
        if ($db != '') {
            $msc->selectDb($db);
        }

        /**
         * Подгружаем и инициализируем функции для работы с БД
         */
        define('DBI_MSC_QUERY_OBJECT', 'msc');
        define('DBI_MSC_QUERY_METHOD', 'query');
        define('DBI_MSC_MSG_OBJECT', 'msc');
        define('DBI_MSC_MSG_METHOD', 'addMessage');
        require_once dirname(__FILE__) . '/DatabaseManager.php';
        require_once dirname(__FILE__) . '/DatabaseTable.php';
        require_once dirname(__FILE__) . '/DatabaseRow.php';
        $dbm = new DatabaseManager();
        $dbm->_init();
        $dbt = new DatabaseTable();
        $dbr = new DatabaseRow();

        // Выполнение запросов
        // Данные из POST должны считываться самостоятельно

        /*ajaxResult([
            'status' => true,
            'message' => ["queryMode=$queryMode db=$db tbl=".(is_array($tbl) ? implode(',', $tbl) : $tbl)]
        ]);
        $msc->addMessage('Выполняем $queryMode='.$queryMode.' $db='.$db.'
            tbl='.(is_array($tbl) ? '['.implode(',', $tbl).']' : $tbl), '', MS_MSG_NOTICE);*/

        switch ($queryMode) {
            case 'querysql':
                $data = [];
                $res = $msc->query($_POST['sql']);
                if ($_POST['type'] == 'pair-value') {
                    while ($row = mysqli_fetch_array($res)) {
                        $data[$row[0]] = $row[1];
                    }
                } else {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $data[] = $row;
                    }
                }
                exit(json_encode($data));

            case 'sqlQuery'    :

                $mysqlGenerationTime0 = round(array_sum(explode(" ", microtime())), 10);
                if (!$msc->selectDb($db)) {
                    return $msc->addMessage('Не смог выбрать базу данных "' . $db . '"', null, MS_MSG_FAULT);;
                }

                $file = 'Z:/marketmixer1.sql';
                //echo 'alert(1);';

                if (!file_exists($file)) {
                    return $msc->addMessage('Файл "' . $file . '" не найден ', null, MS_MSG_FAULT);
                }

                echo '
                insertBefore("sqlQueryForm", "h2", "sqlTitleDiv");
                    remove(get("sqlQueryForm"));
                get("sqlTitleDiv").innerHTML = "<b>Выполняем запросы из файла ' . $file . '<b>";
                
                insertAfter("sqlTitleDiv", "DIV", "sqlQueryDiv");
                get("sqlQueryDiv").style.border = "1px solid #ccc";
                get("sqlQueryDiv").style.padding = "10px";
                get("sqlQueryDiv").style.marginTop = "10px";
                
                insertAfter("sqlTitleDiv", "DIV", "sqlCounterDiv");
                get("sqlCounterDiv").style.border = "1px solid #ccc";
                get("sqlCounterDiv").style.padding = "10px";
                get("sqlCounterDiv").style.marginTop = "10px";
                ';

                function addSqlAjaxLog($txt)
                {
                    echo "\n" . 'get("sqlQueryDiv").innerHTML = "<div>' . $txt . '</div>" + get("sqlQueryDiv").innerHTML;';
                }


                //$sql = file_get_contents($file);

                //addSqlAjaxLog('размер файла '.strpos($file, ";\r\n"));
                break;


                //$msc->logInFile($sql);
                $sql = str_replace("\r\n", "\n", $sql);
                $array = explode(";\n", $sql);
                $errors = array();
                $c = 0;
                $affected = 0;
                $count = count($array);
                //addSqlAjaxLog('Всего запросов в файле: '.$count);
                for ($i = 0; $i < $count; $i++) {
                    $q = trim($array[$i]);
                    if (empty($q) || (strpos($q, '--') === 0 && strpos($q, "\n") === false)) {
                        continue;
                    }
                    $c++;

                    echo "\n" . 'get("sqlCounterDiv").innerHTML = "' . $c . '";';


                }
                $fault = count($errors);
                $succ = $c - $fault;
                $info = " $succ запросов выполнено, $fault неудач. ";
                if (count($errors) == 0) {
                    $msc->addMessage('Запрос выполнен без ошибок - ' . $info, null, MS_MSG_SUCCESS);
                } else {
                    $msc->addMessage('Запросы выполнен с ошибками' . $info . '<br />' . implode('<br />', $errors), null, MS_MSG_FAULT);
                }
                $mysqlGenerationTime = round(round(array_sum(explode(" ", microtime())), 10) - $mysqlGenerationTime0, 5);
                $msc->addMessage("Выполнено за $mysqlGenerationTime с.<br>Затронуто рядов: $affected");


                break;

            // операции с таблицами
            // в запросе обязательно должна быть указана БД и таблица

            case 'tableDelete'    :
                $dbt->tableAction($db, $tbl, 'DROP');
                break;

            case 'tableTruncate'  :
                $dbt->tableAction($db, $tbl, 'TRUNCATE');
                break;

            case 'tableRename':
                if ($dbt->tableAction($db, $tbl, 'RENAME', $this->param('newName'))) {
                    $msc->table = $this->param('newName');
                }
                break;

            case 'tableMove':
                $dbt->queryCheck($db, $tbl, $this->param('newName'), $this->param('newDB'));
                if ($dbt->copyTable($db, $tbl, true, true, $this->param('newName'), $this->param('newDB'))) {
                    $dbt->tableAction($db, $tbl, 'DROP');
                }
                break;

            // Копирование в другую БД
            case 'tableCopyTo';
                $dbt->copyTable($db, $tbl, true, true, $this->param('newName'), $this->param('newDB'));
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
                if ($msc->query($sql)) {
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
                    global $connection;
                    $sql = 'UPDATE `'.$tbl.'` SET '.$field.' = REPLACE(`'.$field.'`, "'.$search_for.'", "'.$replace_in.'")';
                    if ($msc->query($sql)) {
                        $c = mysqli_affected_rows($connection);
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
                $dbt->queryCheck($db);
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
                    $msc->db = null;
                    $msc->page = 'db_list';
                }
                break;

            case 'dbTruncate'     :
                $dbm->DatabaseTruncate($db);
                break;

            case 'dbHide'     :
                if ($this->param('act') == 'show') {
                    $msc->query('REPLACE INTO mysqlcenter.db_info (db_name, visible) VALUES("' . $db . '", 1)');
                    $msc->addMessage("База $db открыта");
                } else {
                    $msc->query('REPLACE INTO mysqlcenter.db_info (db_name, visible) VALUES("' . $db . '", 0)');
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
                $dbm->DatabaseAlterCharset($db, $this->param('charset'), $queryMode == 'dbCharset');
                break;


            case 'dbAllAction'       :
                $tables = DatabaseTable::getCashedTablesArray();
                $action = POST('act');
                foreach ($tables as $o) {
                    if ($action === 'makeInnodb') {
                        if ($o->Engine == 'InnoDB') {
                            $msc->addMessage('Таблица '.$o->Name.' пропущена - уже InnoDB',  '', MS_MSG_NOTICE);
                            continue;
                        }
                        $msc->query($sql='ALTER TABLE `'.$o->Name.'` ENGINE = InnoDB');
                        $error = mysqli_errorx();
                        if ($error) {
                            $msc->addMessage(111, $sql='xxx', MS_MSG_FAULT);
                        }
                    }
                    if ($action === 'drop-query') {
                        $sql = 'DROP TABLE `' . $o->Name . '`;';
                        $msc->addMessage($sql, '', MS_MSG_SUCCESS);
                    }

                    if (in_array($action, ['analyze', 'check', 'flush', 'repair', 'optimize'])) {
                        $sql = strtoupper($action) . ' TABLE `' . $o->Name . '`';
                        if ($msc->query($sql)) {
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

            case 'deleteRow':
                $dbr->rowDelete($db, $tbl, $this->param('row'));
                break;

            case 'copyRow':
                $dbr->rowCopy($db, $tbl, $this->param('row'));
                break;

            case 'rowsAdd':
                require_once DIR_MYSQL . 'includes/tbl_change.inc.php';
                processRowsEdit(1);
                break;

            case 'rowsEdit':
                require_once DIR_MYSQL . 'includes/tbl_change.inc.php';
                processRowsEdit(0);
                break;

            // массовые действия с рядами

            case 'deleteRows' :
            case 'copyRows' :
                $a = $this->param('row');
                if ($a == false) {
                    break;
                }
                $dbr->queryCheck($db, $tbl);
                if ($queryMode == 'copyRows') {
                    $dbr->rowCopy($db, $tbl, implode(' OR ', $a));
                } else if ($queryMode == 'deleteRows') {
                    $dbr->rowDelete($db, $tbl, implode(' OR ', $a), count($a));
                }
                break;

            // операции с полями

            case 'deleteField' :
                $dbm->queryCheck($db, $tbl, $this->param('field'));
                $sql = "ALTER TABLE `$tbl` DROP " . $this->param('field');
                if ($msc->query($sql, $db)) {
                    $msc->addMessage('Поле удалено', $sql, MS_MSG_SUCCESS);
                } else {
                    $msc->addMessage('Ошибка удаления поля', $sql, MS_MSG_FAULT, mysqli_errorx());
                }
                break;

            // Удаление множества полей через POST
            case 'fieldsDelete' :
                $deleteFields = $this->param('field');
                $fields = getFields($tbl);
                // если в таблице осталось только 1 поле, то удаляем таблицу
                if (count($fields) == 1) {
                    $sql = 'DROP TABLE `' . $tbl . '`';
                } else {
                    $sql = 'ALTER table `' . $tbl . '` DROP `' . implode('`, DROP `', $deleteFields) . '`';
                }
                if ($msc->query($sql)) {
                    $msc->addMessage('Таблица изменена', $sql, MS_MSG_SUCCESS);
                } else {
                    $msc->addMessage('Ошибка при изменении таблицы', $sql, MS_MSG_FAULT, mysqli_errorx());
                }
                break;

            // операции с ключами

            case 'deleteKey' :
                $dbm->queryCheck($db, $tbl, $this->param('key'), $this->param('field'));
                $field = $this->param('field');
                if ($this->param('key') == 'PRIMARY') {
                    dropPrimaryKey($tbl);
                } else {
                    $sql = "ALTER TABLE `$tbl` DROP KEY " . $this->param('key');
                    if ($msc->query($sql, $db)) {
                        $msc->addMessage('Ключ удален', $sql, MS_MSG_SUCCESS);
                    } else {
                        $msc->addMessage('Ошибка удаления ключа', $sql, MS_MSG_FAULT, mysqli_errorx());
                    }
                }
                break;

            case 'addKey' :
                $keyName = POST('keyName');
                $keyDefinition = POST('keyType');
                $dbm->queryCheck($db, $tbl, $keyName, $keyDefinition);
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
                if ($msc->query($sql, $db)) {
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
                    if ($msc->query($sql = 'KILL ' . $kill)) {
                        $msc->addMessage('Успешно удалено');
                    } else {
                        $msc->addMessage('Ошибка остановки', $sql, MS_MSG_ERROR, $msc->error);
                    }
                }
                break;

            default:
                return false;

        }

        if (isajax()) {
            ajaxResultWithMessages();
        }
    }
}

