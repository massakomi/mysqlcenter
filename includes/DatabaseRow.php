<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

require_once dirname(__FILE__).'/DatabaseInterface.php';

/**
 * Класс, отвечающий за работу с рядами таблиц баз данных
 */
class DatabaseRow extends DatabaseInterface {

	/**
	 * @access private
	 */
	function __construct() {
		$this->_init();
	}

	/**
	 * Удаляет $limit строк из $table по условию $row
	 *
	 * @param string
	 * @param string
	 * @param string
	 * @param integer
	 * @return boolean
	 */
	function rowDelete($db, $table, $row, $limit=1) {
		$this->queryCheck($db, $table, $row);
		$row = stripslashes(urldecode($row));
		$sql = 'DELETE FROM '.$table.' WHERE '.$row.' LIMIT '.$limit;
		if ($this->query($sql)){
			return $this->addMessage("Ряд $row удалён", $sql, MS_MSG_SUCCESS);
		} else {
			return $this->addMessage("Ошибка удаления ряда $row", $sql, MS_MSG_FAULT, mysqli_errorx());
		}
	}

	/**
	 * Копирует строки $table по условию $row
	 *
	 * @param string
	 * @param string
	 * @param string
	 * @return boolean
	 */
	function rowCopy($db, $table, $row) {
        global $connection;
		$this->queryCheck($db, $table, $row);
		$fields = getFields($table);
		$f = array();
		$ai = null;
		foreach ($fields as $k => $v) {
			if (!strchr($v->Key, 'PRI')) {
				$f []= $v->Field;
			}
			if ($v->Extra != null) {
				$ai = $v->Field;
			}
		}
		if ($ai == null) {
			return $this->addMessage('Невозможно скопировать ряд, т.к. в таблице нет поля auto_increment', null, MS_MSG_FAULT);
		}
		$fields = implode(',', $f);
		$row = stripslashes(urldecode($row));
		$sql = "INSERT INTO $table ($fields) SELECT $fields FROM $table WHERE $row";
		if ($this->query($sql)) {
			$n = mysqli_affected_rows($connection);
			if ($n > 0) {
				return $this->addMessage('Добавлено '.$n.' рядов', $sql, MS_MSG_SUCCESS);
			} else {
				return $this->addMessage('Всё в порядке', $sql, MS_MSG_SUCCESS);
			}
		} else {
			return $this->addMessage('Ошибка копирования ряда '.$row, $sql, MS_MSG_FAULT, mysqli_errorx());
		}
	}
}

?>
