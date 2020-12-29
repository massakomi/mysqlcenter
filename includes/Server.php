<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */
 
/**
 * Класс Server - сервер, где расположены базы данных
 */
class Server
{
	
	/**
	 * Возвращает массив баз данных
	 */
	public static function getDatabases()
    {
		global $connection;
		$db_list = mysqli_query($connection, 'SHOW DATABASES');
		$array = [];
		while ($row = mysqli_fetch_object($db_list)) {
			$array []= $row->Database;
		}
		return $array;
	}
}
?>