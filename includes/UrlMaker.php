<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

class UrlMaker {

	/**
	 * База данных в функции make() может автоматически браться либо из $_GET, либо из $msc
	 * Эта переменная указывает на то, чтобы брать базу данных из $_GET
	 */
	var $useGet = false;
	
	/**
	 * Строка URL, которая используется как базовая (для switcher)
	 */
	var $url;


	/**
	 * Основная функция для создания URL
	 * 
	 * Произвольное количество параметров, которые воспринимаются как param, value строки запроса
	 *   $umaker->make('part', 100, 'mode', 'delete');
	 * При этом можно не указывать таблицу, базу данных или страницу - они будут браться автоматически
	 *
	 * Активно используется в PageLayout
	 *
	 * Если надо использовать БД из $_GET, применяйте код:
	 *  $umaker->useGet = true;
	 *  $url = $umaker->make('part', 100, 'mode', 'delete');
	 *  $umaker->useGet = false;
	 *  
	 * @return string
	 */
	function make() {
		global $msc;
		/**
		 * Надо проверить, что функция не вызывается как статическая.
		 * isset($this) возвращает true всегда, т.к. ещё выше есть ещё один класс PageLayout
		 * поэтому делается допонительная проверка на класс
		 */
		if (!isset($this) || strtolower(get_class($this)) != 'urlmaker') {
			$msc->error('UrlMaker->make cannot be used as static');
			return;
		}	
		$values = func_get_args();
		$count = count($values);
		$array = array();
		$names = array();
		for ($i = 0; $i < $count; $i +=2) {
            if ($values[$i + 1] == '') {
            	continue;
            }
			$array []= $values[$i] . '=' . $values[$i + 1];
			$names []= $values[$i];
		}
		$db = $this->useGet ? GET('db') : $msc->db;
		if ($db != '' && !in_array('db', $names)) {
			array_unshift($array, 'db='.$db);
		}
		if ($msc->table != '' && !in_array('table', $names)) {
			array_unshift($array, 'table='.$msc->table);
		}
		if ($msc->page != '' && !in_array('s', $names)) {
			array_unshift($array, 's='.$msc->page);
		}
		$url = MS_URL . '?' . (count($array) > 0 ? implode('&', $array) : '');
		return $url;
	}
	
	/**
	 * Переключает указанное значение $value1 на $value2 и обратно в УРЛ. Если $value2 не указано, то перключает первое значение.
	 *
	 * @param string 
	 * @param string 
	 * @param string
	 * @return string
	 */
	function switcher($name, $value1, $value2=null) {
		if (GET($name) == null) {
			return UrlMaker::edit($this->url, $name, $value1);
		} else {
			if ($value2 == null) {
				return UrlMaker::delete($this->url, $name);
			} else {
				return UrlMaker::edit($this->url, $name, GET($name) == $value1 ? $value2 : $value1);
			}
		}
	}
	
	/**
	 * Конструктор. Определяет $this->url
	 * @access private
	 */
	function UrlMaker() {
		$this->url = $_SERVER['REQUEST_URI'];
	}
	
	/**
	 * Функция редактирует URL добавляя/заменяя значение переменной name на value
	 */
	function edit($url, $name, $value) {
	  $url = str_replace("&amp;", "&", $url);
	  $first = strpos($url, ($name."="));
	
	  if (is_integer($first)) {
		$c = substr($url, $first-1, 1);
		if (($c == "&") || ($c == "?")) {
		  $result = substr($url, 0, $first);
		  $p = strpos($url, "&", $first);
		  if (is_integer($p)) $result .= substr($url, $p+1);
		  return UrlMaker::edit($result, $name, $value);
		}
	  } else {
		$p = strpos($url, "?");
		if (!is_integer($p)) {
		  $url .= "?";
		} else {
		  $c = substr($url, strlen($url)-1, 1);
		  if (($c != "&") && ($c != "?")) $url .= "&";
		}
		$url .= $name."=".$value;
		$url = str_replace("&", "&amp;", $url);
		return $url;
	  }
	}
	
	/**
	 * 
	 */
	function delete($url, $name) {
		$url = str_replace("&amp;", "&", $url);
		$first = strpos($url, ($name."="));
		if (is_integer($first)) {
			$c = substr($url, $first-1, 1);
			if (($c == "&") || ($c == "?")) {
				$result = substr($url, 0, $first);
				$p = strpos($url, "&", $first);
				if (is_integer($p)) 
					$result .= substr($url, $p+1);
				$c = substr($result, strlen($result)-1, 1);
				if ($c == "&") 
					$result = substr($result, 0, strlen($result)-1);
				$result = str_replace("&", "&amp;", $result);
				return $result;
			}
		} else {
			$url = str_replace("&", "&amp;", $url);
			return $url;
		}
	}	
}


?>