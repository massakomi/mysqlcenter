/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

function xajax(query)
{
    var ajaxdebug = (typeof(debug) != 'undefined' && debug == '1');

    var options = {
        method: 'POST',
        body: new URLSearchParams('?'+query),
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    }

    fetch('ajax.php', options)
        .then(response => {
            if (!response.ok) {
            	console.error(response.status +' ' + response.statusText)
            } else {
                return response.text();
            }
        })
        .then(text => {
            if (text.indexOf('Parse error') != -1) {
            	console.error(text)
            } else {
                try {
                	eval(text);
                } catch(e) {
                    if (ajaxdebug) {
                        console.error('JS код не выполнен: '+text);
                    }
                }
            }
        })
        .catch((error) => {
            console.error(error)
        });
}

/**
 * Общий ajax запрос к серверу. Ответ помещается в "msAjaxQueryDiv".
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

    xajax(query+'&'+'mode='+mode)

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
    var opts = document.querySelectorAll('form[name="'+formName+'"] select[name="'+fieldName+'"] option');
    opts.forEach(function(opt) {
		if (option == 'select') {
			opt.selected = true;
		}
		if (option == 'unselect') {
			opt.selected = false;
		}
		if (option == 'invert') {
			opt.selected = !opt.selected;
		}
    })
}

/**
 * Функция, которая отвечает за механизм отображения/скрытия блока быстрого SQL запроса на всех страницах MSC
 */
function msDisplaySql() {
	if (jQuery('#sqlPopupQueryForm').is(':visible')) {
		jQuery('#sqlPopupQueryForm').hide()
	} else {		
		jQuery('#sqlPopupQueryForm').show().find('textarea').focus()
	}	
}



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