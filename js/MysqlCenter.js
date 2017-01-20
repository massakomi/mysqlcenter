/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

/**
 * Общий ajax запрос к серверу. Ответ помещается в "msAjaxQueryDiv".
 *
 * @use xla.send()
 *
 * @param  string  Режим запроса
 * @param  string  Строка запроса urldecoded
 * @return boolean false
 */
function msQuery(mode, query) {
	if (mode != 'tableRename' && mode != 'dbCreate'  && mode != 'dbHide' && confirm('Подтвердите...') == false) {
		return false;
	}
	if (arguments.length == 0) {
		return false;
	} else if (arguments.length == 1) {
		var query = '';
	}
    query = query.replace(/^\?/, '')
	xla.send(query+'&'+'mode='+mode);
	return false;
}

/**
 * Присваивает полю 'image_action' значение param и отправляет форму (для image кнопок)
 * @ actionReplace - новое значение action формы (опционально)
 */
function msImageAction(formName, param, actionReplace) {
	if (param.match(/delete/i) || param.match(/truncate/i)) {
		if (!confirm('Подтвердите...')) {
			return false;
		}
	}
	var f = document.getElementsByName(formName);
	var forma = f[0];
	forma['action'].value = param;
	if (!is_null(actionReplace)) {
		forma.setAttribute('action', actionReplace);
	}
	forma.submit();
}

/**
 * Групповые действия со множественным селектором (select multiply)
 *
 * @param string Аттрибут name формы
 * @param string Аттрибут name элемента SELECT
 * @param string Опция обработки select|unselect|invert
 */
function msMultiSelect(formName, fieldName, option) {
	var f = document.getElementsByName(formName);
	var forma = f[0];
	var sel = forma[fieldName];
	for (var i = 0; i < sel.options.length; i ++) {
		if (option == 'select') {
			sel.options[i].selected = true;
		}
		if (option == 'unselect') {
			sel.options[i].selected = false;
		}
		if (option == 'invert') {
			sel.options[i].selected = !sel.options[i].selected;
		}			
	}
}

/**
 * Функция, которая отвечает за механизм отображения/скрытия блока быстрого SQL запроса на всех страницах MSC
 */
function msDisplaySql() {
	var s = document.getElementById('sqlPopupQueryForm').style;
	if (s.display == 'block') {
		s.display = 'none'
	} else {		
		s.display = 'block';
		document.getElementById('sqlPopupQueryForm')['sql'].focus();
	}	
}

/*
key = {
	needkey:function(e) {
		var code;
		if (!e) var e = window.event;
		if (e.keyCode) code = e.keyCode;
		else if (e.which) code = e.which;
		//window.status = code + '-' + e.ctrlKey + '-' + e.altKey
		//alert(code + '-' + e.ctrlKey + '-' + e.altKey)
		// Обзор  ALT + R
		if ((code == 82) && (e.ctrlKey == false) && (e.altKey == true))
			window.location = '?s=tbl_data&table=<?php echo $msc->table?>';

		// Структура  ALT + S
		if ((code == 83) && (e.ctrlKey == false) && (e.altKey == true))
			window.location = '?s=tbl_struct&table=<?php echo $msc->table?>';

		// Список таблиц  ALT + T
		if ((code == 84) && (e.ctrlKey == false) && (e.altKey == true))
			window.location = '?s=tbl_list';

		// Быстрый экспорт в файл  CTRL + ALT + F или F
		//if ((code == 70) && (e.ctrlKey == false) && (e.altKey == false))
			//window.location = '?s=export&table=<?php echo $msc->table?>&action=quick2';
	}
}
if (document.getElementById) {
		document.onkeydown = key.needkey;
}
*/


function createFlyBlock (id, style) {
	if (!(element = document.getElementById(id))) {
		var element = document.createElement('div');
		document.body.appendChild(element);
		for (var i in style) {
			element.style[i] = style[i];
		}
	}
	element.id = id;
	return element;
}
// трассировка
function trace(txt) {
	var d = createFlyBlock('traceBlock', {
		border   : '1px solid black',
		backgroundColor : '#ffffff',
		padding  : '5px',
		fontSize : '10px',
		color    : '#000000',
		position : 'absolute',
		top      : '10px',
		left     : '10px',
		zIndex  :  '100',
	});
	d.innerHTML = d.innerHTML + txt;
}