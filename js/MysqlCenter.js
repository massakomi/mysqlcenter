/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/*


Все эти функции предназначены для ajax.php то есть только для ActionProcessor событий

msQuery(mode, query) - обертка над xajax  query+'&'+'mode='+mode  (db_list, tbl_list, tbl_struct)
xajax - делает запрос на ajax.php + options POST + response eval -- по сути больше нигде не используется

-------------------
querySql - асинхронный аналог xajax
querySql - выполнить запрос удаленно - возвращает Promise, делает запрос на ajax + options POST, querySql(params, responseType='text') - server_variables, msc_help
  // пример применения
  async function printTable(sql) {
    let data = await querySql({sql, mode: 'querysql'}, 'json')
    for (let item of data) {
    }
  }

async loadAll() {
    sql = 'SHOW GLOBAL VARIABLES';
    mode = 'querysql'
    type = 'pair-value'
    let globalVars = await querySql({sql, mode, type}, 'json')
    this.setState({globalVars, sessionVars})
}

componentDidMount () {
    this.loadAll()
}
-------------------


apiQuery async - (НЕ РАБОТАЕТ) fetch GET json return -

*/

/**
 * Общий ajax запрос к серверу. Ответ помещается в "msAjaxQueryDiv".
 *
 * @param  mode string  Режим запроса
 * @param  query string  Строка запроса urldecoded
 * @param callback
 * @return boolean false
 */
async function msQuery(mode, query = '', callback = '') {
    if (mode.match(/delete/i) && confirm('Подтвердите...') === false || arguments.length === 0) {
        return false;
    }

    loader()
    let response = await fetch('', getFetchOptions(mode, query))
    loader()

    return await queryResponse(response, callback);
}

/**
 * @param response
 * @param callback
 * @param type
 * @returns {Promise<{error: boolean, message: string}|*>}
 */
async function queryResponse(response, callback, type = 'json') {
    let ajaxdebug = (typeof (debug) != 'undefined' && debug);
    if (response.ok) {
        let content;
        if (type === 'text') {
            content = await response.text();
            if (content.indexOf('Parse error') !== -1) {
                console.error(content)
            } else {
                try {
                    eval(content);
                } catch (e) {
                    if (ajaxdebug) {
                        console.error('JS код не выполнен: ' + content);
                    }
                }
            }
        } else {
            try {
                content = await response.json();
                showMessages(content)
            } catch (e) {
                let message = 'Ошибка ' + e.name + ":" + e.message + "\n" + e.stack;
                showError(message);
                return {error: true, message};
            }
        }
        if (callback && typeof (callback) == 'function') {
            callback(content)
        }
        return content;
    } else {
        console.error(response.status + ' ' + response.statusText)
        let error;
        try {
            error = await response.json();
            showError(`${error.message} <span class="text-black-50">${error.file}</span>`);
        } catch (e) {
            let message = `${response.status} ${response.statusText}`;
            showError(message);
            error = {error: true, message}
        }
        // вопрос - что тут возвращать, false, response или error???
        // В есть 2 момента. 1. На каких то страницах лучше не открывать Модал если пришла ошибка. Как это определить. Удобно либо false либо response.ok
        // 2. В Admin когда приходит false я не могу вывести ошибку в модалке, не знаю ее, но и закрывать модалку не хочу, не нужно
        // В теории возвращать response. если очень нужно прочитать ошибку - можно еще раз сделать json  ХЗ пока
        return error;
    }
}

function showMessages(json) {
    if (!json.messages) {
        return;
    }

    let div = document.getElementById('msAjaxQueryDiv')
    div.classList.add('visible')

    if (div.querySelectorAll('table').length > 2) {
        div.querySelector('table:last-child').remove()
    }

    let messages = [];
    for (let message of json.messages) {
        let textError = message.text
        if (message.sql !== '' && message.sql !== null) {
            let aff = `<br /><span style="color:#ccc">затронуто рядов: ${message.rows}}</span>`
            textError += `<div class="sqlQuery">${message.sql}; ${aff}</div>`
        }
        if (message.error !== '' && message.error !== null) {
            textError += `<div class="mysqlError"><b>Ошибка:</b> ${message.error}</div>`
        }
        messages.push(textError)
    }
    messages = messages.join('<br />')

    let messageId = 'msg-' + Math.random()
    div.insertAdjacentHTML('afterbegin', `
        <table class="globalMessage">
        <tr><th>Сообщение <a href="#" class="hiddenSmallLink" style="color:#fff" onClick="showhide('${messageId}')">close</a></th></tr>
        <tr id="${messageId}"><td>${messages}</td></tr>
        </table>`);

    if (typeof (msAjaxQueryDivTm) != 'undefined') {
        clearTimeout(msAjaxQueryDivTm);
    }
    msAjaxQueryDivTm = setTimeout(function () {
        div.classList.remove('visible')
    }, 5000);
}

/**
 *
 * @param mode
 * @param query
 * @returns RequestInit
 */
function getFetchOptions(mode, query) {
    let body = null
    if (typeof (query) === 'string') {
        query = query.replace(/^\?/, '')
        body = new URLSearchParams(query)
    } else if (query instanceof Element) {
        body = new FormData(query)
    } else if (typeof (query) === 'object') {
        body = new URLSearchParams(query)
    } else {
        alert('Unknown fetch options!')
    }
    body.set('mode', mode)
    body.set('ajax', 1)
    return {
        method: 'POST',
        body: body,
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    }
}

function showError(message) {
    const el = document.querySelector('#errorMessage');
    if (el !== null) {
        el.lastTime = (Date.now() / 1000).toFixed(0)
        el.classList.remove('d-none')
        el.innerHTML = message
    } else {
        console.error(message)
    }
}

// umaker({db: 'xxx'})
function umaker(query = {}, doSwitch = false) {
    let u = new URL(location.href)
    for (let key in query) {
        if (query[key] === false) {
            u.searchParams.delete(key)
            continue;
        }
        if (doSwitch) {
            if (new URL(location.href).searchParams.get(key) === query[key]) {
                if (doSwitch === true) {
                    u.searchParams.delete(key)
                    continue;
                } else if (typeof doSwitch == "object" && doSwitch[key]) {
                    query[key] = doSwitch[key]
                }
            }
        }
        u.searchParams.set(key, query[key])
    }
    return u.href;
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
    let f = document.getElementsByName(formName);
    let forma = f[0];
    forma['action'].value = param;
    if (!is_null(actionReplace)) {
        forma.setAttribute('action', actionReplace);
    }
    forma.submit();
}

/**
 * Функция, которая отвечает за механизм отображения/скрытия блока быстрого SQL запроса на всех страницах MSC
 */
function msDisplaySql() {
    let form = document.getElementById('sqlPopupQueryForm')
    if (form.checkVisibility()) {
        form.style.display = 'none'
    } else {
        form.style.display = 'block'
        form.querySelector('textarea').focus()
    }
}


/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Пакет общих функций и библиотек для разработки приложений
 * @pack 13.03.2010
 */



/**
 * Копирует последний ряд таблицы вниз
 * @param  tableId string   id таблицы
 * @return object Вставленная строка
 */
function addRow(tableId, from = 'last', after = true) {
    var table = document.getElementById(tableId);
    // сколько всего рядов
    var i = table.rows.length;
    // берём последний/первый ряд
    var tr = table.rows[from == 'last' ? i - 1 : (from > i ? i - 1 : from)];
    // назначаем ему ид
    tr.id = 'trAfterId' + i;
    // вставляем после/до него еще 1 строку
    if (!after) {
        insertBefore('trAfterId' + i, 'TR', 'trNewId' + i);
    } else {
        insertAfter('trAfterId' + i, 'TR', 'trNewId' + i);
    }
    // вот она!
    var tr2 = document.getElementById('trNewId' + i);
    // копируем ячейки из одной строки в другую
    for (var j = 0; j < tr.cells.length; j++) {
        td = document.createElement('TD')
        tr2.appendChild(td)
        td.innerHTML = tr.cells[j].innerHTML
    }
    return tr2;
}

/**
 * Вставляет элемент после другого элемента
 */
function insertAfter(sAfterId, sTag, sId) {
    let objSibling = document.getElementById(sAfterId);
    objElement = document.createElement(sTag);
    objElement.setAttribute('id', sId);
    objSibling.parentNode.insertBefore(objElement, objSibling.nextSibling);
}

function insertBefore(sAfterId, sTag, sId) {
    let objSibling = document.getElementById(sAfterId);
    objElement = document.createElement(sTag);
    objElement.setAttribute('id', sId);
    objSibling.parentNode.insertBefore(objElement, objSibling);
}

/**
 * Удаляет ряд таблицы с конца
 */
function removeRow(tableId) {
    let r = document.getElementById(tableId).rows;
    if (r.length === 1) {
        return false;
    }
    remove(r[r.length - 1]);
}

/**
 * Удаляет элемент
 */
function remove(objElement) {
    if (objElement && objElement.parentNode && objElement.parentNode.removeChild) {
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
    if (trim(val) === '') {
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
    return (typeof (v) == 'undefined');
}

function trim(s) {
    s = s.replace(/[\s\t\r\n]+$/, '')
    return s.replace(/^[\s\t\r\n]+/, '')
}

/**
 * Показать / скрыть элемент
 * Внимание! первоначальный style.display должен быть назначен скриптом, иначе он будет не виден
 */
function showhide(id) {
    if (typeof (id) != 'object') {
        id = document.getElementById(id);
    }
    if (id.style.display === '') {
        id.style.display = 'block';
    }
    if (id.style.display === 'none') {
        id.style.display = 'block';
    } else {
        id.style.display = 'none';
    }
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
function chbx_action(form_name, action, mask = false) {

    var add = '';
    if (mask) {
        add = '[name="' + mask + '"]'
    }
    var chbxs = document.querySelectorAll('form[name="' + form_name + '"] input[type="checkbox"]' + add);
    for (var chx of chbxs) {
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
    set: function (name, value, expires, path, domain, secure) {
        expl = new Date();
        expires = expl.getTime() + (expires * 24 * 60 * 60 * 1000);
        expl.setTime(expires);
        expires = expl.toGMTString();
        var curCookie = name + "=" + escape(value) +
          ((expires) ? "; expires=" + expires : "") +
          ((path) ? "; path=" + path : "") +
          ((domain) ? "; domain=" + domain : "") +
          ((secure) ? "; secure" : "")
        if ((name + "=" + escape(value)).length <= 4000)
            document.cookie = curCookie
        else if (confirm("Cookie превышает 4KB и будет вырезан !"))
            document.cookie = curCookie;
        return curCookie;
    },
    get: function (name) {
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

formatSize = (bytes, digits = 0) => {
    if (bytes < Math.pow(1024, 1)) {
        return bytes + " b";
    } else if (bytes < Math.pow(1024, 2)) {
        return (bytes / Math.pow(1024, 1)).toFixed(digits) + ' Kb';
    } else if (bytes < Math.pow(1024, 3)) {
        return (bytes / Math.pow(1024, 2)).toFixed(digits) + ' Mb';
    } else if (bytes < Math.pow(1024, 4)) {
        return (bytes / Math.pow(1024, 3)).toFixed(digits) + ' Gb';
    }
}

function dbHiddenMenu() {
    let hideTimeout = null;
    let div = document.getElementById('dbHiddenMenu')
    forElementsEvent('mouseover', '#appNameId', function(e) {
        div.style.display = 'block'
    })

    forElementsEvent('mouseout', '#dbHiddenMenu', function(e) {
        let w = parseInt(window.getComputedStyle(div).width);
        if (e.pageX > w) {
            hideTimeout = setTimeout(function () {
                div.style.display = 'none'
            }, 300);
        }
    })

    forElementsEvent('mouseover', '#dbHiddenMenu', function(e) {
        if (hideTimeout != null) {
            clearInterval(hideTimeout);
        }
    })
    forElementsEvent('click', '#dbHiddenMenu', function(e) {
        div.style.display = 'none'
    })
}

function ctrlKeyMode() {
    // Определяет активность клавиши CTRL
    window.globalCtrlKeyMode = false;
    window.key = {
        needkey: function (e) {
            if (globalCtrlKeyMode === true) {
                globalCtrlKeyMode = false;
            }
            if (e.ctrlKey === true && e.type === 'keydown') {
                globalCtrlKeyMode = true;
            }
        }
    }
    if (document.getElementById) {
        document.onkeydown = key.needkey;
        document.onkeyup = key.needkey;
    }
}

function searchEvents() {
    forElementsEvent('focus', '.search-top [name="query"]', function () {
        this.classList.add('wide')
        if (this.value.indexOf('Поиск') === 0) {
            this.dataset['default'] = this.value
            this.value = '';
        }
    })
    forElementsEvent('blur', '.search-top [name="query"]', function () {
        this.classList.remove('wide')
        if (this.dataset['default']) {
            this.value = this.dataset['default']
        }
    })
    forElementsEvent('change', '.search-top [name="field"]', function () {
        document.querySelector('.search-top [name="byField"]').value = ''
    })
    forElementsEvent('focus', '.search-top [name="byField"]', function () {
        document.querySelector('.search-top [name="query"]').value = ''
        this.style.width = 'auto'
    })
}

function mysqlCenterInit() {
    document.addEventListener("DOMContentLoaded", function() {
        dbHiddenMenu();
        ctrlKeyMode();
        searchEvents();
    });
}

function htmlspecialchars(text) {
    if (typeof (text) != 'string') {
        return text
    }
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    return text.replace(/[&<>"']/g, function (m) {
        return map[m];
    });
}

function loader() {
    document.querySelector('.loader').toggleAttribute('hidden')
}

/**
 * (для tbl_data и tbl_compare) Обрабатывает значения полей базы данных перед выводом их в виде таблицы.
 * Обработка заключается в: для текстовых - htmlspecialchars+обрезка, для даты - отображение в поле id=tblDataInfoId
 * для нулевых значений - значение возвращается оформленным курсивом.
 *
 * @package data view
 * @return string Обработанное значение
 * @param v
 * @param type
 * @param textCut
 */
function processRowValue(v, type, textCut) {
    if (v === null) {
        v = 'null'
    } else {
        // Тексты
        if (type.match(/(blob|text|char)/i)) {
            v = htmlspecialchars(v)
        }
        if (v.length > textCut) {
            let fullText = new URL(location.href).searchParams.get('fullText');
            if (fullText === null) {
                v = v.substring(0, textCut)
            }
        }
    }
    return v
}

// gQuery to js

function forElements(selector, callback) {
    let all = [].slice.call(document.querySelectorAll(selector))
    all.map(function (el) {
        callback.call(el)
    })
}

function forElementsEvent(event, selector, callback) {
    forElements(selector, function() {
        this.addEventListener(event, function(e){
            callback.call(this, e)
        })
    })
}

// Возвращает число, под которым элемент находится на текущем уровне (аналог jQuery.index() )
function getElementIndex(el) {
    return Array.prototype.indexOf.call(el.parentNode.children, el)
}

function isNumeric(value) {
    if (typeof(value) == 'number') {
        return true
    } else if (typeof(value) == 'string') {
        if (value.match(/^\d+$/) !== null) {
            return true
        }
    }
    return false
}

function qs(selector) {
    let element = document.querySelector(selector)
    if (element == null) {
        element = document.createElement('div')
    }
    return element
}