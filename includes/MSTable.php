<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

/**
 * Класс для работы с собственной БД приложения
 */
class MSTable {
	
	var $data;

	/**
	 * Возвращает все переменные сета
	 */
	function getSetInfo($idSet) {
		if ($idSet == null) {
			return array();
		}
		global $msc;
		$result = $msc->query('SELECT * FROM mysqlcenter.export_table WHERE id_set='.$idSet);
		$a = array();
		while ($o = mysqli_fetch_object($result)) {
			$a [$o->table_name]= $o;
		}
		return $a;
	}
	
	/**
	 * Возвращает массив сетов
	 */
	function getSetsArray() {
		global $msc;
		$result = $msc->query('SELECT * FROM mysqlcenter.export_set');
		$a = array();
		while ($o = mysqli_fetch_object($result)) {
			$a [$o->id]= $o->name;
		}
		return $a;
	}	
	
	/**
	 * Возвращает массив сетов
	 */
	function getHiddensArray() {
		global $msc;
		$result = $msc->query('SELECT * FROM mysqlcenter.db_info WHERE visible=0');
		$a = array();
		while ($o = mysqli_fetch_object($result)) {
			$a []= $o->db_name;
		}
		return $a;
	}
	
	/**
	 * Добавляет новый сет
	 */
	function insertSet($name) {
		global $msc;
		if ($msc->query('INSERT INTO mysqlcenter.export_set (`name`) VALUES ("'.$name.'")')) {
			return mysqli_insert_id();
		} else {
			return false;
		}
	}
	
	/**
	 * Вставляет новую переменню сета
	 */
	function insertOption($id_set, $table_name, $struct, $data, $where_sql, $pk_top) {
        global $msc;
		if ($struct + $data == 0) {
			return false;
		}
		$sql = "INSERT INTO mysqlcenter.export_table(id_set, table_name, struct, data, where_sql, pk_top) VALUES ('$id_set', '$table_name', '$struct', '$data', '$where_sql', '$pk_top')";
		if ($msc->query($sql)) {
			return mysqli_insert_id();
		} else {
			return false;
		}
	}
}

?>