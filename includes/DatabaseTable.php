<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

require_once dirname(__FILE__).'/DatabaseInterface.php';

/**
 * Класс, отвечающий за работу с таблицами базы данных
 */
class DatabaseTable extends DatabaseInterface {

	/**
     * @access private
	 */
	function __construct($db=null, $table=null) {
		$this->_init();
		$this->database = $db;
		$this->tableb   = $table;
		$this->table    = '`'.str_replace('`', '``', $table).'`';
	}

	/**
	 * Совершает действие типа $type с $table, используя если надо параметр $param
	 *
	 * @todo  проверка существования таблицы
	 * @param string 
	 * @param string
	 * @param string  DROP | TRUNCATE | ANALISE | OPTIMIZE | CHECK | REPAIR | FLUSH
	 * @param string
	 * @return boolean
	 */
	function tableAction($db, $table, $type='DROP', $param=null) {
        global $msc;
		$this->queryCheck($db, $table);
		if (($type == 'RENAME' || $type == 'CHARSET' || $type == 'ORDER') && $param == null) {
			return $this->addMessage('Не указан требуемый параметр', null, MS_MSG_ERROR);
		}
		switch ($type) {
		case 'DROP'	    : $sql = "DROP TABLE `$table`";     $text = 'удалена';    break;
		case 'TRUNCATE'	: $sql = "TRUNCATE TABLE `$table`"; $text = 'очищена';    break;
		case 'CHECK'	: $sql = "CHECK TABLE `$table`";    $text = 'обработана'; break;
		case 'ANALYZE'	: $sql = "ANALYZE TABLE `$table`";  $text = 'обработана'; break;
		case 'REPAIR'	: $sql = "REPAIR TABLE `$table`";   $text = 'обработана'; break;
		case 'OPTIMIZE'	: $sql = "OPTIMIZE TABLE `$table`"; $text = 'обработана'; break;
		case 'FLUSH'	: $sql = "FLUSH TABLE `$table`";    $text = 'обработана'; break;
		case 'RENAME'	: $sql = "ALTER TABLE `$table` RENAME `$param`";                    $text = 'переименована'; break;
		case 'CHARSET'	: $sql = "ALTER TABLE `$table` CONVERT TO CHARACTER SET `$param`";  $text = 'изменена'; break;
		case 'COMMENT'	: $sql = "ALTER TABLE `$table` COMMENT = '$param'";                 $text = 'изменена'; break;
		case 'ORDER'	: $sql = "ALTER TABLE `$table` ORDER BY $param";                    $text = 'изменена'; break;
		default :
			return $msc->addMessage('Неверный тип обработки', null, MS_MSG_ERROR);
		}
		if ($this->query($sql, $db)){
			return $msc->addMessage("Таблица $table $text", $sql, MS_MSG_SUCCESS);
		} else {
			return $msc->addMessage("Ошибка при выполнении операции с таблицей $table", $sql, MS_MSG_FAULT, mysqli_error());
		}
	}

	/**
	 * Копирует таблицу $table, если надо со структурой, данными. если надо переименовывает
	 *
	 * @param string  текущая БД
	 * @param string  таблица
	 * @param boolean надо ли создавать таблицу на новом месте
	 * @param boolean надо ли копировать данные
	 * @param string  новое имя, если таблица переименовывается
	 * @param string  База данных, куда надо копировать
	 * @return boolean
	 */
	function copyTable($db, $table, $struct=true, $data=false, $newName=null, $database=null) {
        global $msc;
		$this->queryCheck($db, $table);
		if (!class_exists('MySQLExport')) {
			require_once dirname(__FILE__).'/Export.class.php';
		}
		// дамп структуры
		if ($newName == null) {
			$newName = $table . '_copy';
		}
		if ($database == null) {
			$database = $db;
		}
		$exp = new MySQLExport();
		$exp->setDatabase($db);
		$exp->setTable($table);
		$sql = $exp->exportStructure(1, false);
		$sql = preg_replace('/CREATE TABLE ([a-zA-Z0-9_`\-]+)/i', 'CREATE TABLE `' . $newName . '`', $sql, 1);
		$errorsFull = 0; // сохраняем ошибки, а не выходим, чтобы не прерывать процесс копирования
		if ($this->query($sql, $database)) {
			$msc->addMessage("Таблица скопирована", $sql, MS_MSG_SUCCESS);
		} else {
			$errorsFull++;
			$msc->addMessage("Ошибка копирования", $sql, MS_MSG_FAULT);
		}
		// переход в старую БД после запроса
		if ($database != $db) {
			$msc->selectDb($db);
		}
		// дамп данных
		if ($data) {
			if ($database != $db) {
				$sql = 'INSERT INTO '.$database.'.'.$newName.' SELECT * FROM '.$db.'.'.$table;
			} else {
				$sql = "INSERT INTO $newName SELECT * FROM $table";
			}
			if ($this->query($sql)) {
				$msc->addMessage('Данные скопированы', $sql, MS_MSG_SUCCESS);
			} else {
				$errorsFull++;
				$msc->addMessage('Ошибка копирования данных', $sql, MS_MSG_FAULT, mysqli_error());
			}
		}
		return ($errorsFull == 0);
	}
	
	/**
	 * Проверяет существование таблицы $this->table
	 *
	 * @return boolean
	 */
	function isExists() {
		global $msc;
		$sql = 'SELECT COUNT(1) AS c FROM '.$this->table;
		$msc->query($sql);
		$this->exists = (mysqli_error() == null);
		return $this->exists;
	}
	
	/**
	 * Возвращает таблицу с полной информацией о таблице $this->table
	 *
	 * @return string
	 */
	function insertDetailsTable() {
		global $msc;
		$sql = 'SHOW TABLE STATUS LIKE "'.$this->tableb.'"';
		$result = $msc->query($sql);
		$comments = array(
            'Engine' => ' title="Тип хранилища"',
            'Version' => ' title="Версия .frm файла таблицы"',
            'Row_format' => ' title="Формат хранения строки (Fixed, Dynamic, Compressed, Redundant, Compact). Начиная с MySQL/InnoDB 5.0.3, InnoDB таблицы хранятся в форматах Redundant или Compact. До 5.0.3, InnoDB таблицы всегда были в формате Redundant"',
            'Rows' => ' title="Количество рядов. Некоторые типы хранилищ, такие как MyISAM, отображают точное количество. Но в некоторых других, таких как InnoDB, это значение является приблизительным и может отличаться от действительного количество на 40-50%. В таких случаях лучше всего использовать запрос SELECT COUNT(*). Также это значение равно NULL для таблиц INFORMATION_SCHEMA базы данных"',
            'Avg_row_length' => ' title="Средняя длина строки"',
            'Data_length' => ' title="Размер файла данных таблицы"',
            'Max_data_length' => ' title="Максимальный размер файла данных. Это общее количество байтов данных, которое может быть сохранено в таблице, given the data pointer size used."',
            'Index_length' => ' title="Размер индексного файла"',
            'Data_free' => ' title="Размер занятого, но не использованного пространства"',
            'Auto_increment' => ' title="Следующее значение поля Auto_increment"',
            'Update_time' => ' title="Когда дата файл был обновлён. Для некоторых типов хранилищ, это значение NULL. Например, InnoDB хранит таблицы в собственном хранилище и время изменения файла данных не даст ничего"',
            'Check_time' => ' title="Когда таблицы были проверены в последний раз. Не все типы хранилищ обновляют этот параметр, в этих случаях он всегда NULL"',
            'Collation' => ' title="Кодировка и сравнение таблиц"',
            'Checksum' => ' title="The live checksum value (if any)."',
            'Create_options' => ' title="Дополнительные опции, заданные при создании таблицы через CREATE TABLE."',
            'Comment' => ' title="Комментарий, заданный при создании таблицы (либо информация о том, почему MySQL не может получить доступ к информации о таблице"'
        );
		return MSC_printObjectTable($result, true, '', $comments);
	}
	
	/**
	 * Возвращает таблицу с полной информацией о структуре $this->table
	 *
	 * @return string
	 */
	function insertStructTable() {
		global $msc;
		$sql = 'DESCRIBE `'.$this->tableb.'`';
		$result = $msc->query($sql);
		$table = new Table(null, 1, 3, 0);
		return MSC_printObjectTable($result, false, $table);		
	}	
	


  public static function getCashedTablesArray() {
    global $msc;
  	if ($msc->db == 'pr_marketmixer' && isset($_SESSION['table_menu_array'])) {
      $msc->addMessage('Список таблиц закэширован на сессию', null, MS_MSG_NOTICE);
  		return $_SESSION['table_menu_array'];
  	}

    $result = $msc->query('SHOW TABLE STATUS');

  	if (!$result || mysqli_num_rows($result) == 0) {
  		return array();
  	}

    $array = array();
    while ($row = mysqli_fetch_object($result)) {
      $array []= $row;
    }

  	if ($msc->db == 'pr_marketmixer' && !isset($_SESSION['table_menu_array'])) {
  		$_SESSION['table_menu_array'] = $array;
  	}

  	return $array;
  }
	
	
}


?>