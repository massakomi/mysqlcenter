/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

/**
 * Пакет общих функций и библиотек для разработки приложений
 * @pack 13.03.2010
 */

$ = function (id) {
    return document.getElementById(id);
}
get = function (id) {
    return document.getElementById(id);
}


/**
 * Копирует последний ряд таблицы вниз
 * @param  string  id таблицы
 * @return object Вставленная строка
 */
function addRow(tableId, from='last', after=true) {
	var table = $(tableId);
	// сколько всего рядов
	var i     = table.rows.length;
	// берём последний/первый ряд
	var tr    = table.rows[from == 'last' ? i - 1 : (from > i?i-1:from)];
	// назначаем ему ид
	tr.id = 'trAfterId' + i;
	// вставляем после/до него еще 1 строку
	if (!after) {
  	insertBefore('trAfterId' + i, 'TR', 'trNewId' + i);
  } else {
  	insertAfter('trAfterId' + i, 'TR', 'trNewId' + i);
  }
	// вот она!
	var tr2 = $('trNewId' + i);
	// копируем ячейки из одной строки в другую
	for (var j = 0; j < tr.cells.length; j ++) {
		td = document.createElement('TD')
		tr2.appendChild(td)
		td.innerHTML = tr.cells[j].innerHTML
	}
	return tr2;
}

/**
 * Вставляет элемент после другого элемента
 */
function insertAfter (sAfterId, sTag, sId){
	var objSibling = $(sAfterId);
	objElement = document.createElement(sTag);
	objElement.setAttribute('id',sId);
	objSibling.parentNode.insertBefore(objElement, objSibling.nextSibling);
}
function insertBefore (sAfterId, sTag, sId){
	var objSibling = $(sAfterId);
	objElement = document.createElement(sTag);
	objElement.setAttribute('id',sId);
	objSibling.parentNode.insertBefore(objElement, objSibling);
}

/**
 * Удаляет ряд таблицы с конца
 */
function removeRow(tableId) {
	var r = $(tableId).rows;
	if (r.length == 1) {
        return false;
    }
	remove(r[r.length - 1]);
}

/**
 * Удаляет элемент
 */
function remove(objElement)	{
	if (objElement && objElement.parentNode && objElement.parentNode.removeChild)	{
		objElement.parentNode.removeChild(objElement);
	}
}

/**
 * Покаывает сообщение об ошибке, если элемент формы не заполнен
 *
 * @param   object   Форма
 * @param   string   Аттрибут name проверяемого поля *
 * @return  boolean  сабмитит форму
 */
function checkEmpty(forma, fieldName) {
	var val = forma[fieldName].value;
	if (trim(val) == '') {
		forma[fieldName].select();
		alert('Поле пустое');
		forma[fieldName].focus();
		return false;
	} else {
		forma.submit();
		return true;
	}
}

// Полейзнейший набор функций
function is_null(v) {
	return (typeof(v) == 'undefined');
}
function trim(s) {
	s = s.replace(/[\s\t\r\n]+$/, '')
	return s.replace(/^[\s\t\r\n]+/, '')
}
function str_replace(srch, repl, str) {
	while (str.indexOf(srch) != -1) {
		str = str.replace(srch, repl)
	}
    return str
}

/**
 * Показать / скрыть элемент
 * Внимание! первоначальный style.display должен быть назначен скриптом, иначе он будет не виден
 */
function showhide(id) {
    if (typeof(id) != 'object') {
        id = $(id);
    }
	if (id.style.display == '') {
		id.style.display = 'block';
	}
	if (id.style.display == 'none') {
		id.style.display = 'block';
	} else {
		id.style.display = 'none';
	}
}

/**
 * Перемещает объект 'obj' к точке (x, y)
 */
function moveto(obj, x, y) {	
	oCanvas = document.getElementsByTagName((document.compatMode && document.compatMode == "CSS1Compat") ? "HTML" : "BODY")[0];	
	w_width = oCanvas.clientWidth ? oCanvas.clientWidth + oCanvas.scrollLeft : window.innerWidth + window.pageXOffset;
	w_height = window.innerHeight ? window.innerHeight + window.pageYOffset : oCanvas.clientHeight + oCanvas.scrollTop;
	
	t_width = obj.offsetWidth;
	t_height = obj.offsetHeight;
	
	obj.style.position = 'absolute'
	obj.style.left = x + "px";
	obj.style.top = y + "px";
	
	if (x + t_width> w_width) obj.style.left = w_width - t_width + "px";
	if (y + t_height> w_height) obj.style.top = w_height - t_height + "px";
}

/**
 * Определяем top - left координаты блока obj
 */
function absPosition(obj) { 
	this.x = 0;
	this.y = 0;
	this.w = obj.offsetWidth;
	this.h = obj.offsetHeight;
	while(obj) {
		this.x += obj.offsetLeft;
		this.y += obj.offsetTop;
		obj = obj.offsetParent;
	}
	return {x:this.x, y:this.y, w:this.w, h:this.h};
}

/**
 * Координта мышки в данный момент
 */
function getCurs(e) {
	oCanvas = document.getElementsByTagName(
	(document.compatMode && document.compatMode == "CSS1Compat") ? "HTML" : "BODY"
	)[0];
	x = window.event ? event.clientX + oCanvas.scrollLeft : e.pageX;
	y = window.event ? event.clientY + oCanvas.scrollTop : e.pageY;
	return {x:x, y:y}
}

/**
 * Подтверждение перехода по ссылке
 * ! обязательно передавать this, т.к. без него нельзя передать message
 */
function check(obj, message) {
	if (is_null(message)) {
		message = 'текущее действие';
	}
	if (confirm('Подтвердите: ' + message)) {
		window.location.href = obj.href
	} else {
		return false
	}
}


/**
 * Групповые действия с чекбоксами
 */
function chbx_action(form_name, action, mask) {
	var f = document.getElementsByName(form_name);
	var forma = f[0];
	var e = forma.elements;
	var chx;
	var chbx = 0;
	for (i = 0; i < e.length; i ++) {
		chx = e[i];
		if (chx.type != 'checkbox') {
			continue;
		}
		name_chbx = chx.name		
		if (name_chbx != mask) {
			continue;
		}
		chbx ++;
		if (action == 'invert') {
			chx.checked = !chx.checked;
		} else if (action == 'check') {
			chx.checked = true;
		} else if (action == 'uncheck') {
			chx.checked = false;	
		} 	
	}
}

/**
 * Устанавливает / возвращает cookie
 */
cook = {
	set:function(name, value, expires, path, domain, secure) {
		expl=new Date();
		expires=expl.getTime() + (expires*24*60*60*1000);
		expl.setTime(expires);
		expires=expl.toGMTString();
		var curCookie = name + "=" + escape(value) +
		((expires) ? "; expires=" + expires: "") +
		((path) ? "; path=" + path : "") +
		((domain) ? "; domain=" + domain : "") +
		((secure) ? "; secure" : "")
		if ((name + "=" + escape(value)).length <= 4000)
			document.cookie = curCookie
		else
			if (confirm("Cookie превышает 4KB и будет вырезан !"))
				document.cookie = curCookie;
		return curCookie;		
	},
	get:function(name) {
		var prefix = name + "=";
		var cookieStartIndex = document.cookie.indexOf(prefix);
		if (cookieStartIndex == -1)
				return false
		var cookieEndIndex = document.cookie.indexOf(";", cookieStartIndex + prefix.length);
		if (cookieEndIndex == -1)
				cookieEndIndex = document.cookie.length;
		return unescape(document.cookie.substring(cookieStartIndex + prefix.length, cookieEndIndex))		
	}	
}

/**
 * Назначает выполнение функции 'a' при наступлении события 'e' с объектом 'o'
 */
function list(object, event, action) {
	if (object.addEventListener) {
        object.addEventListener(event, action, false);
    } else if (object.attachEvent) {
        object.attachEvent("on" + event, action);
    } else {
        return null;
    }
}
/**
 * Удаляет событие от элемента
 */
function rlist(object, event, a){
	if (object.removeEventListener) {
        object.removeEventListener(event, action, false);
    } else if (object.detachEvent) {
        object.detachEvent("on" + event, action);
    } else {
        return null;
    }
}