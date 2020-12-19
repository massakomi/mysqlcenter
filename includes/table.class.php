<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */


/**
 * Класс Table для создания красивых таблиц средствами PHP
 * Главное - простое добавление данных
 * v 2.3 (04.09.2008)
 *
 * Пример использования:
 * $table = new Table(null, 1, 5, 0, '100%');
 * $table->makeRowHead('Имя', 'Должность', 'Город');
 * $table->makeRow('Иван Иванов', 'Программист', 'Москва');
 * echo $table->make();
 */
 
 
class Table {	
	var $className, $border, $cpadd, $cspace, $width, $id, $tableTitle, $tableStyle; // top
	var $tableCont, $rowsCont, $headerCont;
	var $interlace, $colClass, $headerClass;
	var $rowsCount;  // счётчик рядов
	var $rn   = "\r\n";
	var $tab  = '  '; // отступ td от tr (tab)
	var $btab = '  '; // отступ tr (base tab)
	var $interlaceClass;
	
	/**
	 * Конструктор: позволяет задать заголовок h3 для таблицы, а также имя класса
	 * Значения $border, $cp, $cs учитываются только если не указан класс
	 */
	function __construct($class=null, $border=null, $cpadd=null, $cspace=null, $width=null, $id=null) {
		if (strpos($class, ':') === false) {
			$this->className = $class;
		} else {
			$this->tableStyle = $class;
		}		
		$this->border = $border;
		$this->cpadd = $cpadd;
		$this->cspace = $cspace;
		$this->width = $width;
		$this->id = $id;
	}

	/** 	
	 * Завершает создание таблицы
	 */
	function make() {
		if ($this->tableTitle != null) {
			$this->tableCont .= '<h3>'.$this->tableTitle.'</h3>'.$this->rn;
		}
		
		$attr = null;
		!is_null($this->className)  ? $attr .= ' class="'.$this->className.'"' : true;
		!is_null($this->tableStyle)  ? $attr .= '  style="'.$this->tableStyle.'"' : true;
		!is_null($this->border) ? $attr .= ' border="'.$this->border.'"' : true;
		!is_null($this->cpadd)  ? $attr .= ' cellpadding="'.$this->cpadd.'"' : true;
		!is_null($this->cspace) ? $attr .= ' cellspacing="'.$this->cspace.'"' : true;
		!is_null($this->width)  ? $attr .= ' width="'.$this->width.'"' : true;
		!is_null($this->id)     ? $attr .= ' id="'.$this->id.'"' : true;
	  
		$this->tableCont .= '<table'.$attr.'>' . $this->rn;
		
		if ($this->headerCont != null) {
			$this->tableCont .= '<thead>' . $this->rn;
			$this->tableCont .= $this->headerCont;
			$this->tableCont .= '</thead>' . $this->rn;			
		}
		
		$this->tableCont .= '<tbody>' . $this->rn;
		$this->tableCont .= $this->rowsCont;
		$this->tableCont .= '</tbody>' . $this->rn;
		
		$this->tableCont .= '</table>' . $this->rn;

		return $this->tableCont;
	}

	
	/**
	 * На лету создаёт ряд в новой таблице
	 * @ $dataArray - массив данных (строки или числа)
	 * @ $attr      - атрибуты тега <tr> 
	 * @ $isHead    - использвать тег <th> и считать ряд заголовком таблицы
	 * @ $attrArray - массив атрибутов ячеек
	 *   * можно передать данные как аргументы (a1, a2, a3 ...), но тогда $attr передавать нельзя
	 *   * если установлен interlace, то атрибут bgcolor использовать нельзя	 
	 */
	function makeRow($dataArray, $attr = null, $isHead = false, $attrArray = null) {
		$this->rowsCount ++;
		if (!is_array($dataArray)) {
			$dataArray = func_get_args();
			$attr = null; // блокировка
			$isHead = false;
			$attrArray = null;
		}
		if ($isHead) {
			$this->headerCont .= $this->buildRow($dataArray, $attr, $isHead, $attrArray);
		} else {
			// interlace не для заголовка
			if ($this->interlace != null) {
				if ($this->rowsCount % 2 == 0) {
					$this->interlace[0] == '' ? true : $attr .= ' bgcolor="'.$this->interlace[0].'"';
				} else {
					$this->interlace[1] == '' ? true : $attr .= ' bgcolor="'.$this->interlace[1].'"';
				}
			}
			if ($this->interlaceClass != null) {
				if ($this->rowsCount % 2 == 0) {
					$this->interlaceClass[0] == '' ? true : $attr .= ' class="'.$this->interlaceClass[0].'"';
				} else {
					$this->interlaceClass[1] == '' ? true : $attr .= ' class="'.$this->interlaceClass[1].'"';
				}
			}
			$this->rowsCont .= $this->buildRow($dataArray, $attr, false, $attrArray);
		}		
	}
	
	/** 
	 * [Продолжение makeRow()]
	 */
	function buildRow($dataArray, $attr = null, $isHeader = false, $attrArray = null) {
		$row = $this->rn.$this->btab."<tr$attr>";
		foreach ($dataArray as $k => $v) {
			$t = null;
			if (isset($this->headerClass[$k]) && $this->headerClass[$k] != null && $isHeader) {
				$t = $this->headerClass[$k];
			}
			if (isset($this->colClass[$k]) && $this->colClass[$k] != null && !$isHeader) {
				$t = $this->colClass[$k];
			}
			$extra = '';
			if (!is_null($t)) {				
				if (strchr($t, ':')) {
				  $extra .= ' style="'.$t.'"';
				} else {
					$extra .= ' class="'.$t.'"';
				}
			}

			if (!is_null($attrArray) && isset($attrArray[$k])) {			
				$extra .= $attrArray[$k];
			}
			if ($isHeader) {
				$row .= $this->rn.$this->btab.$this->tab.'<th'.$extra.'>'.$v.'</th>';
			} else {
				$row .= $this->rn.$this->btab.$this->tab.'<td'.$extra.'>'.$v.'</td>';
			}			
		}
		$row .= $this->rn.$this->btab.'</tr>';
		return $row;
	}

	/**
	 * Создаёт заголовочный ряд (аналог makeRow($args))
	 */
	function makeRowHead($dataArray, $attr = null) {
		if (!is_array($dataArray)) {
			$dataArray = func_get_args();
			$attr = null; // блокировка
		}
		$this->headerCont = $this->buildRow($dataArray, $attr, true);
	}
	
	// устанавливает код, который надо вставить в шапку таблицы (custom header)
	function setHeadContent($a) {
		$this->headerCont = $a;
	}
	
	/**
	 * Классы или стили колонок
	 */
	function setColClass($classes) {
		$this->colClass = func_get_args();
	}
	
	function setHeaderClass($class) {
		$this->headerClass = func_get_args();
	}
	
	/**
	 * Черезстрочность таблицы
	 */
	function setInterlace($color1, $color2) {
		$this->interlace = func_get_args();
	}
	function setInterlaceClass($class1, $class2) {
		$this->interlaceClass = func_get_args();
	}

	/**
	 * Опции создания кода
	 * @ $rn  - разбивка на строки
	 * @ $tab - еденица отступа
	 */	
	function setCodeOptions($rn, $tab) {
		$this->rn  = $rn;
		$this->tab = $tab;
	}
	
	/**
	 * SET функции общие
	 */	
	function setTableTitle($a) {  $this->tableTitle = $a;	}	
	function setId($a) {		      $this->id = $a;	}
	function setClassName($a) {		$this->className = $a;	}
	// и т.д.
}


?>