
/**
 * Реализация ajax запросов на сервер
 * версия от 13.03.2010
 *
 * Пример применения
 * var xla = new XLAjax('ajax.php')
 * xla.send('id=1')
 *
 * @param string HTTP путь к скрипту, который будет принимать параметры запроса и отправлять результат
 */
function XLAjax(file) {

	this.file = file;

	/**
	 * Отправка ajax запроса на сервер.
	 * После получения успешного результата выполняет полученный responseText как скрипт,
	 * иначе в случае предварительной установки var debug='1', делает _trace() ошибки
	 *
	 * @param string Строка URL запроса "name=value&..."
	 */
	this.send = function(action) {
		this.http = this._createObject();
		this.http.open('POST', this.file, true);
		this.http.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=WINDOWS-1251");
		this.http.onreadystatechange = this._handleResponse;
		this.http.send(action);
	};
	


// РЕАЛИЗАЦИЯ

	/**
	 * Функция, которая выполняется после получения результата
	 */
	this._handleResponse = function() {
        var ajaxdebug = (typeof(debug) != 'undefined' && debug == '1');
		if (xla.http.readyState == 4){
			var response = xla.http.responseText;
            if (response == '') {
                return;
            }
            try {
            	eval(response);
            } catch(e) {
                if (ajaxdebug)
                    xla._trace('JS код не выполнен: '+response);
            }
		}
		if (ajaxdebug && xla.http.readyState == 3 && xla.http.responseText.indexOf('Parse error') != -1){
            xla._trace('readyState == 3: '+xla.http.responseText);
		}
	};
	
	/**
	 * Возвращает XMLHttp объект для ajax запроса
	 *
	 * @return XMLHttp объект
	 */
	this._createObject = function() {
		var request = null;
		if (typeof XMLHttpRequest != "undefined") {
			try {
				request = new XMLHttpRequest();
			} catch(e) {
				request = null;
			}
		} else if(window.ActiveXObject) {
			try {
				request = new ActiveXObject("Msxml2.XMLHTTP");
			} catch(e) {
				try {
					request = new ActiveXObject("Microsoft.XMLHTTP");
				} catch(e) {
					request = null;
				}
			}
		}
		return request;
	};
	
    /**
     * Выводит сообщение во всплывающем блоке
     *
     * @param string Сообщение
     * @param string Опция отображения: status|title|fly
     */
    this._trace = function(s, mode) {
    	if (mode == 'status') {
    		window.status += s;
    	} else if (mode == 'title') {
    		document.title += s;
    	} else {
    		var d = this._createFlyBlock('traceBlock', {
    			border   : '1px solid black',
    			backgroundColor : '#ffffff',
    			padding  : '5px',
    			fontSize : '10px',
    			color    : '#000000',
    			position : 'absolute',
    			top      : '10px',
    			left     : '10px'
    		});
    		d.innerHTML = d.innerHTML + s + '<br />';
    	}
    }

    /**
     * Возвращает простой блок для trace 
     *
     * @param string ID существующего или создаваемого блока
     * @return HTML Element
     */
    this._createFlyBlock = function(id, style) {
    	if (!(element = document.getElementById(id))) {
    		var element = document.createElement('div');
    		document.body.appendChild(element);
    		if (typeof(style) != 'undefined') {
    			for (var i in style) {
    				element.style[i] = style[i];
    			}
    		}
    	}
    	element.id = id;
    	return element;
    }
}



/*
http
+open
+setRequestHeader
+onreadystatechange
+send
+readyState               1,2,4
+responseText
channel
responseXML
status                   200
statusText               OK
abort
getAllResponseHeaders    Полный заголовок
getResponseHeader        -
overrideMimeType
multipart
onload
onerror
onprogress
addEventListener
removeEventListener
dispatchEvent
*/