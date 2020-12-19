<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

require_once dirname(__FILE__).'/DatabaseInterface.php';

/**
 * Класс, отвечающий за работу с базой данных
 */
class DatabaseManager extends DatabaseInterface {
	
	var $tables = array();
	

	/**
	 * Удаляет / создаёт БД
	 *
	 * @param string База данных
	 * @param string DROP|CREATE
	 * @return boolean
	 */
	function DatabaseAction($db, $type='DROP') {
        global $msc;
		$this->queryCheck($db);
		switch ($type) {
		case 'DROP'   : $sql = "DROP DATABASE `$db`";   $text = 'удалена'; break;
		case 'CREATE' : $sql = "CREATE DATABASE `$db`"; $text = 'создана'; break;
		default :
			return $msc->addMessage('Неверный тип обработки', null, MS_MSG_ERROR);
		}
		if ($this->query($sql)){
			return $msc->addMessage("База данных $db $text", $sql, MS_MSG_SUCCESS);
		} else {
			return $msc->addMessage("Ошибка работы с $db", $sql, MS_MSG_FAULT, mysqli_error());
		}
	}

	/**
	 * Удаляет / очищает все таблицы БД
	 *
	 * @param string База данных
	 * @param boolean Если true - удалить таблицы, иначе очистить
	 * @return boolean true только если ошибок нет, false - если хотя бы одна таблица не обработана
	 */
	function DatabaseTruncate($db, $delete=false) {
		require_once  dirname(__FILE__).'/DatabaseTable.php';
		$dbt = new DatabaseTable();
		$this->queryCheck($db);
		$a = DatabaseManager::getTables($db);
		if (count($a) == 0) {
			return $this->addMessage('Таблиц нет', null, MS_MSG_FAULT);
		}
		$errors = 0;
		foreach ($a as $t) {
			if ($delete) {
				if (!$dbt->tableAction($db, $t, 'DROP')) {
					$errors ++;
				}
			} else {
				if ($dbt->tableAction($db, $t, 'TRUNCATE')) {
					$errors ++;
				}
			}
		}
		return ($errors == 0);
	}

	/**
	 * Копирует / переименовывает БД
	 *
	 * @param string БД-источник
	 * @param string БД, куда копируется/перемещается
	 * @param boolean Если true, то перемещает, иначе копирует
	 * @param boolean Если true, копирует структуру (CREATE TABLE...), иначе нет
	 * @param boolean Если true, копирует данные, иначе нет
	 * @return boolean
	 */
	function DatabaseCopy($dbFrom, $dbTo, $isMove=false, $struct=true, $data=true) {
		if ($this->DatabaseAction($dbTo, 'CREATE')) {
			require_once  dirname(__FILE__).'/DatabaseTable.php';
			$dbt = new DatabaseTable();
			// скопировать все таблицы туда и удалить из старой БД
			$a = DatabaseManager::getTables($dbFrom);
			foreach ($a as $table) {
				$dbt->copyTable($dbFrom, $table, $struct, $data, $table,  $dbTo);
				if ($isMove) {
					$dbt->tableAction($dbFrom, $table, 'DROP');
				}
			}
			// удалить БД
			if ($isMove) {
				return $this->DatabaseAction($dbFrom, 'DROP');
			}
			return true;
		}
		return false;
	}

	/**
	 * Изменяет кодировку или сравнение БД
	 *
	 * @param string БД
	 * @param string Кодировка
	 * @param boolean Если true, то меняется кодировка, иначе сравнение
	 * @return boolean
	 */
	function DatabaseAlterCharset($db, $charset, $isCharset=true) {
        global $msc;
		$this->queryCheck($db, 'table', $charset);
		if (!$isCharset) {
			$sql = "ALTER DATABASE $db COLLATE `$charset`";
		} else {
			$sql = "ALTER DATABASE $db CHARACTER SET `$charset`";
		}
		if ($this->query($sql, $db)) {
			return $msc->addMessage("Успешно выполнено", $sql, MS_MSG_SUCCESS);
		} else {
			return $msc->addMessage("Ошибка при выполнении операции с $db", $sql, MS_MSG_FAULT, mysqli_error());
		}
	}

	/**
	 * Возвращает массив таблиц базы данных
	 *
	 * @param string База данных
	 * @return array
	 */
	public static function getTables($database=null){
		global $msc, $connection;
		if (!is_null($database)) {
			if (!is_null($msc)) {
				$msc->selectDb($database);
			} else {
				mysqli_select_db($connection, $database);
			}
		}
		
        $tables = DatabaseTable::getCashedTablesArray();
		$array = array();
		foreach ($tables as $o) {
			$array[]= $o->Name;
		}
		return $array;
	}
}
?>