<?php

declare(strict_types=1);

namespace controller;

use database\Server;
use database\Table;
use service\UrlMaker;
use service\Validate;

/**
 * Управление запросами. Здесь должны быть централизованы все запросы на изменение данных
 * Это позволит все запросы совершать как через URL, так и через AJAX.
 */
class ActionProcessor
{
    // Куда перенаправлять в случае не ajax запроса
    public string $redirect = '';

    // Common properties extracted from databaseActions method
    private Table $dbt;
    private Server $server;
    private Validate $validate;
    private mixed $db;
    private mixed $tables;

    /**
     * @return false|void
     *
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
                return;
            }
        }

        if (isAjax()) {
            ajaxResultWithMessages();
        } elseif ($this->redirect) {
            header('Location: '.$this->redirect);
        }
    }

    public function generalActions(string $action): bool
    {
        return false;
    }

    /**
     * Действия с базой данных.
     *
     * @throws \Exception
     */
    public function databaseActions(string $action): bool
    {
        global $msc;

        if (!$msc->connected()) {
            return false;
        }

        $this->db = $this->param('db');
        $this->tables = $this->param('table');

        if ($this->db != '') {
            $msc->selectDb($this->db);
        }

        // Initialize common dependencies
        $this->dbt = new Table();
        $this->server = new Server();
        $this->validate = new Validate();

        // Dispatch to the appropriate action method by naming convention: {action}Action
        // querysql - legacy exception due to camelCase method name
        $methodName = ($action === 'querysql') ? 'querySqlAction' : $action.'Action';

        if (!method_exists($this, $methodName)) {
            return false;
        }

        return $this->$methodName($action);
    }

    /**
     * Выполнение SQL запросов.
     */
    private function querySqlAction(string $action): bool
    {
        global $msc;

        $sql = POST('sql');
        $type = POST('type');
        if (preg_match('~^\s*(update|delete|insert|drop|create|alter|--)~i', $sql)) {
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
            if ($msc->error) {
                $msc->error('Ошибка запроса');
            } else {
                $msc->success('Запрос выполнен, затронуто рядов: '.$msc->affectedRows, $msc->lastSql);
            }
            ajaxResultWithMessages();
        }

        return true;
    }

    /**
     * Удаление таблицы.
     */
    private function tableDeleteAction(string $action): bool
    {
        $result = $this->dbt->tableAction($this->db, $this->tables, 'DROP');
        if ($result) {
            $this->redirect = "?s=tbl_list&db={$this->db}";
        }

        return true;
    }

    /**
     * Очистка таблицы.
     */
    private function tableTruncateAction(string $action): bool
    {
        $result = $this->dbt->tableAction($this->db, $this->tables, 'TRUNCATE');
        if ($result) {
            $this->redirect = UrlMaker::make('s', 'tbl_data', 'action', '');
        }

        return true;
    }

    /**
     * Переименование таблицы.
     */
    private function tableRenameAction(string $action): bool
    {
        global $msc;

        if ($this->dbt->tableAction($this->db, $this->tables, 'RENAME', $this->param('newName'))) {
            $msc->table = $this->param('newName');
            $this->redirect = UrlMaker::make('table', $msc->table, 'action', '');
        }

        return true;
    }

    /**
     * Перемещение таблицы.
     */
    private function tableMoveAction(string $action): bool
    {
        if ($this->validate->queryCheck($this->db, $this->tables, $this->param('newName'), $this->param('newDB'))) {
            if ($this->dbt->copyTable($this->db, $this->tables, true, true, $this->param('newName'), $this->param('newDB'))) {
                $this->dbt->tableAction($this->db, $this->tables, 'DROP');
            }
        }

        return true;
    }

    /**
     * Копирование таблицы в другую БД.
     */
    private function tableCopyToAction(string $action): bool
    {
        $withData = !$this->param('tableCopyNoData');
        $this->dbt->copyTable($this->db, $this->tables, true, $withData, $this->param('newName'), $this->param('newDB'));

        return true;
    }

    /**
     * Изменение кодировки таблицы.
     */
    private function tableCharsetAction(string $action): bool
    {
        $this->dbt->tableAction($this->db, $this->tables, 'CHARSET', $this->param('charset'));

        return true;
    }

    /**
     * Изменение опций таблицы.
     */
    private function tableOptionsAction(string $action): bool
    {
        global $msc;

        $ai = intval($this->param('auto_increment'));
        if ($msc->driver->setAutoIncrement($this->tables, $ai)) {
            $msc->success('Таблица изменена', $msc->lastSql);
        } else {
            $msc->error('Ошибка изменения таблицы', $msc->lastSql);
        }

        return true;
    }

    /**
     * Изменение комментария таблицы.
     */
    private function tableCommentAction(string $action): bool
    {
        // !!! внимание, некоторые действия должны выполнятся только с POSTa
        // если идёт пустой GET запрос, он всё перетирает!!!
        if (count($_POST) == 0) {
            return true;
        }
        $this->dbt->tableAction($this->db, $this->tables, 'COMMENT', $this->param('comment'));

        return true;
    }

    /**
     * Поиск и замена в таблице.
     */
    private function tableReplaceAction(string $action): bool
    {
        global $msc;

        $field = POST('field');
        $search_for = POST('search_for');
        $replace_in = POST('replace_in');
        if ($field && $search_for) {
            $sql = 'UPDATE `'.$this->tables.'` SET '.$field.' = REPLACE(`'.$field.'`, "'.
                $search_for.'", "'.$replace_in.'")';
            if ($msc->execPdo($sql)) {
                $c = $msc->affectedRows;
                if ($c > 0) {
                    $msc->success('Таблица изменена, затронуто рядов: '.$c, $sql);
                } else {
                    $msc->error('Ничего не найдено и не заменено', $sql);
                }
            } else {
                $msc->error('Ошибка при изменении таблицы', $sql);
            }
        }

        return true;
    }

    /**
     * Изменение порядка в таблице.
     */
    private function tableOrderAction(string $action): bool
    {
        if (count($_POST) == 0) {
            return true;
        }
        $this->dbt->tableAction($this->db, $this->tables, 'ORDER', '`'.$this->param('field').'` '.$this->param('order'));

        return true;
    }

    /**
     * Проверка таблицы.
     */
    private function tableCheckAction(string $action): bool
    {
        $this->dbt->tableAction($this->db, $this->tables, 'CHECK');

        return true;
    }

    /**
     * Анализ таблицы.
     */
    private function tableAnalizeAction(string $action): bool
    {
        $this->dbt->tableAction($this->db, $this->tables, 'ANALYZE');

        return true;
    }

    /**
     * Восстановление таблицы.
     */
    private function tableRepairAction(string $action): bool
    {
        $this->dbt->tableAction($this->db, $this->tables, 'REPAIR');

        return true;
    }

    /**
     * Оптимизация таблицы.
     */
    private function tableOptimizeAction(string $action): bool
    {
        $this->dbt->tableAction($this->db, $this->tables, 'OPTIMIZE');

        return true;
    }

    /**
     * Сброс таблицы.
     */
    private function tableFlushAction(string $action): bool
    {
        $this->dbt->tableAction($this->db, $this->tables, 'FLUSH');

        return true;
    }

    /**
     * Массовые действия с таблицами (delete_all, truncate_all, copy_all).
     */
    private function bulkTableAction(string $action): bool
    {
        global $msc;

        if (!is_array($this->tables)) {
            $msc->error('table не массив');

            return true;
        }
        if (!$this->validate->queryCheck($this->db)) {
            return true;
        }
        $copyStruct = (POST('copy_struct') != '');
        $copyData = (POST('copy_data') != '');
        foreach ($this->tables as $table) {
            if ($action == 'delete_all') {
                $this->dbt->tableAction($this->db, $table, 'DROP');
            } elseif ($action == 'truncate_all') {
                $this->dbt->tableAction($this->db, $table, 'TRUNCATE');
            } elseif ($action == 'copy_all') {
                $this->dbt->copyTable($this->db, $table, $copyStruct, $copyData);
            }
        }

        return true;
    }

    /**
     * Массовое удаление таблиц.
     */
    private function delete_allAction(string $action): bool
    {
        return $this->bulkTableAction($action);
    }

    /**
     * Массовая очистка таблиц.
     */
    private function truncate_allAction(string $action): bool
    {
        return $this->bulkTableAction($action);
    }

    /**
     * Массовое копирование таблиц.
     */
    private function copy_allAction(string $action): bool
    {
        return $this->bulkTableAction($action);
    }

    /**
     * Удаление базы данных.
     */
    private function dbDeleteAction(string $action): bool
    {
        global $msc;

        if ($this->param('dbMulty')) {
            $databases = $this->param('databases');
        } else {
            $databases = [$this->param('dbDelete')];
        }
        if ($databases) {
            foreach ($databases as $db) {
                $deletedCurrentDb = $db == $msc->db;
                $this->server->databaseAction($db, 'DROP');
                if ($deletedCurrentDb) {
                    $msc->clearCurrentDatabase();
                }
            }
            $this->redirect = '?s=db_list';
        }

        return true;
    }

    /**
     * Очистка базы данных.
     */
    private function dbTruncateAction(string $action): bool
    {
        $this->server->databaseTruncate($this->db);

        return true;
    }

    /**
     * Скрытие/отображение базы данных.
     */
    private function dbHideAction(string $action): bool
    {
        global $msc;

        if ($this->param('act') == 'show') {
            $msc->execPdo('REPLACE INTO mysqlcenter.db_info (db_name, visible) VALUES("'.$this->db.'", 1)');
            $msc->success("База {$this->db} открыта");
        } else {
            $msc->execPdo('REPLACE INTO mysqlcenter.db_info (db_name, visible) VALUES("'.$this->db.'", 0)');
            $msc->success("База {$this->db} скрыта");
        }

        return true;
    }

    /**
     * Удаление всех таблиц из базы данных.
     */
    private function dbTablesDeleteAction(string $action): bool
    {
        $this->server->databaseTruncate($this->db, true);
        $this->redirect = '?s=tbl_list';

        return true;
    }

    /**
     * Создание базы данных.
     */
    private function dbCreateAction(string $action): bool
    {
        $this->server->databaseAction($this->param('dbName'), 'CREATE', $this->param('user'), $this->param('option'));

        return true;
    }

    /**
     * Изменение кодировки/сопоставления базы данных.
     */
    private function dbCharsetAction(string $action): bool
    {
        global $msc;

        if ($this->server->databaseAlterCharset($this->db, $this->param('charset'), $action == 'dbCharset')) {
            $msc->success('Успешно выполнено', $msc->lastSql);
        } else {
            $msc->error("Ошибка при выполнении операции с {$this->db}", $msc->lastSql);
        }

        return true;
    }

    /**
     * Алиас для изменения сопоставления базы (совместимость с dbCollate).
     */
    private function dbCollateAction(string $action): bool
    {
        return $this->dbCharsetAction($action);
    }

    /**
     * Массовое действие с таблицами базы данных.
     */
    private function dbAllAction(string $action): bool
    {
        global $msc;

        $act = POST('act');
        foreach ($this->tables as $table) {
            if (in_array($act, ['analyze', 'check', 'flush', 'repair', 'optimize'])) {
                $sql = strtoupper($act).' TABLE `'.$table.'`';
                if ($msc->fetchPdo($sql)) {
                    $msc->success('Запрос выполнен', $sql);
                } else {
                    $msc->error('Ошибка запроса', $sql);
                }
            }
        }

        return true;
    }

    /**
     * Переименование/копирование базы данных.
     */
    private function dbRenameAction(string $action): bool
    {
        return $this->processDbRenameCopy(true);
    }

    /**
     * Копирование базы данных.
     */
    private function dbCopyAction(string $action): bool
    {
        return $this->processDbRenameCopy(false);
    }

    /**
     * Общая логика переименования/копирования базы данных.
     */
    private function processDbRenameCopy(bool $isMove): bool
    {
        global $msc;

        if ($this->param('dbMulty')) {
            $databases = $this->param('databases');
            $newName = [];
            foreach ($databases as $db) {
                $new = $db.'_copy';
                if (in_array($new, $databases)) {
                    $new = $db.'_copy'.rand(1, 100);
                }
                $newName[] = $new;
            }
        } else {
            $databases = [$this->db];
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
                $this->server->databaseCopy($db, $newName[$k], $isMove, $struct, $data);
            }
            if ($isMove || POST('switch') != null) {
                $msc->db = $newName[$k]; // last
            }
        }

        return true;
    }

    /**
     * Удаление рядов.
     */
    private function deleteRowsAction(string $action): bool
    {
        global $msc;

        $row = $this->param('row');
        if (!$this->validate->queryCheck($this->db, $this->tables, $row)) {
            return true;
        }
        if (!is_array($row)) {
            $row = [$row];
        }
        if ($this->dbt->rowDelete($this->db, $this->tables, implode(' OR ', $row)) && $msc->affectedRows > 0) {
            $msc->success("Рядов удалёно: $msc->affectedRows", $msc->lastSql);
        } else {
            $msc->error('Ошибка удаления ряда', $msc->lastSql);
        }

        return true;
    }

    /**
     * Алиас для удаления одного/нескольких рядов (совместимость с deleteRow).
     */
    private function deleteRowAction(string $action): bool
    {
        return $this->deleteRowsAction($action);
    }

    /**
     * Копирование рядов.
     */
    private function copyRowsAction(string $action): bool
    {
        global $msc;

        $row = $this->param('row');
        if (!$this->validate->queryCheck($this->db, $this->tables, $row)) {
            return true;
        }
        if (is_array($row)) {
            $row = implode(' OR ', $row);
        }
        if ($this->dbt->rowCopy($this->tables, $row)) {
            $n = $msc->affectedRows;
            if ($n > 0) {
                $msc->success('Добавлено '.$n.' рядов', $msc->lastSql);
            } else {
                $msc->success('Всё в порядке', $msc->lastSql);
            }
        } else {
            $text = 'Ошибка копирования ряда '.$row;
            $msc->error($text, $msc->lastSql);
        }

        return true;
    }

    /**
     * Алиас для копирования одного/нескольких рядов (совместимость с copyRow).
     */
    private function copyRowAction(string $action): bool
    {
        return $this->copyRowsAction($action);
    }

    /**
     * Удаление поля.
     */
    private function deleteFieldAction(string $action): bool
    {
        global $msc;

        if (!$this->validate->queryCheck($this->db, $this->tables, $this->param('field'))) {
            return true;
        }
        $sql = "ALTER TABLE `{$this->tables}` DROP ".$this->param('field');
        if ($msc->execPdo($sql, $this->db)) {
            $msc->success('Поле удалено', $sql);
        } else {
            $msc->success('Ошибка удаления поля', $sql);
        }

        return true;
    }

    /**
     * Удаление множества полей.
     */
    private function fieldsDeleteAction(string $action): bool
    {
        global $msc;

        $deleteFields = $this->param('field');
        $fields = Table::getFields($this->tables);
        // если в таблице осталось только 1 поле, то удаляем таблицу
        if (count($fields) == 1) {
            $sql = 'DROP TABLE `'.$this->tables.'`';
        } else {
            $sql = 'ALTER table `'.$this->tables.'` DROP `'.implode('`, DROP `', $deleteFields).'`';
        }
        if ($msc->execPdo($sql)) {
            $msc->success('Таблица изменена', $sql);
        } else {
            $msc->error('Ошибка при изменении таблицы', $sql);
        }

        return true;
    }

    /**
     * Удаление ключа.
     */
    private function deleteKeyAction(string $action): bool
    {
        global $msc;

        if (!$this->validate->queryCheck($this->db, $this->tables, $this->param('key'), $this->param('field'))) {
            return true;
        }
        if ($this->param('key') == 'PRIMARY') {
            Table::dropPrimaryKey($this->tables);
        } else {
            $sql = "ALTER TABLE `{$this->tables}` DROP KEY ".$this->param('key');
            if ($msc->execPdo($sql)) {
                $msc->success('Ключ удален', $sql);
            } else {
                $msc->error('Ошибка удаления ключа', $sql);
            }
        }

        return true;
    }

    /**
     * Добавление ключа.
     */
    private function addKeyAction(string $action): bool
    {
        global $msc;

        $keyName = POST('keyName');
        $keyDefinition = POST('keyType');
        if (!$this->validate->queryCheck($this->db, $this->tables, $keyName, $keyDefinition)) {
            return true;
        }
        if ($keyName != '') {
            $keyDefinition .= ' `'.$keyName.'`';
        }
        $keyFields = [];
        foreach ($_POST['field'] as $key => $fieldName) {
            if ($fieldName == '') {
                continue;
            }
            $fieldSize = $_POST['length'][$key];
            $keyFields[] = '`'.$fieldName.'`'.($fieldSize > 0 ? "($fieldSize)" : '');
        }
        $sql = 'ALTER TABLE '.$this->tables.' ADD '.$keyDefinition.' ('.implode(',', $keyFields).')';
        if ($msc->execPdo($sql)) {
            $msc->success('Ключ добавлен', $sql);
        } else {
            $msc->error('Ошибка создания ключа', $sql);
        }

        return true;
    }

    /**
     * Остановка процесса.
     */
    private function killProcessAction(string $action): bool
    {
        global $msc;

        $kill = POST('id');
        if (!empty($kill)) {
            if ($msc->execPdo($sql = 'KILL '.$kill)) {
                $msc->success('Успешно удалено');
            } else {
                $msc->error('Ошибка остановки', $sql);
            }
        }

        return true;
    }

    /**
     * Создание схемы (PostgreSQL).
     */
    private function schemaAddAction(string $action): bool
    {
        global $msc;

        $name = POST('name');
        if (!empty($name)) {
            if ($msc->execPdo($sql = 'CREATE SCHEMA  '.$name)) {
                $msc->success('Схема успешно создана');
            } else {
                $msc->error('Ошибка создания схемы', $sql);
            }
        }

        return true;
    }

    /**
     * Создание задачи в Yougile.
     */
    private function yougileAction(): bool
    {
        global $msc;

        $task = POST('task');

        $yougileService = new \service\Yougile();
        $result = $yougileService->createTask($task);
        if ($result === null) {
            $msc->error('Ошибка создания задачи');
        } else {
            $msc->success('Задача создана');
        }

        return true;
    }

    /**
     * Возвращает параметр запроса.
     *
     * @param string $name Имя параметра
     *
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
