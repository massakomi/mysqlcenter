<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */
 
/**
 * Класс Server - сервер, где расположены базы данных
 */
class Server {
	
	/**
	 * Возвращает массив баз данных
	 */
	function getDatabases() {
		global $connection;
		$db_list = mysql_list_dbs($connection);
		$array = array();
		while ($row = mysql_fetch_object($db_list)) {
			$array []= $row->Database;
		}
		return $array;
	}
}
?>