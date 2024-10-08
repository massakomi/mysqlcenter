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




function xajax(query)
{
  let ajaxdebug = (typeof(debug) != 'undefined' && debug == '1');

  let options = {
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
      if (text.indexOf('Parse error') !== -1) {
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
}*/

/**
 * Общий ajax запрос к серверу. Ответ помещается в "msAjaxQueryDiv".
 *
 * @param  mode string  Режим запроса
 * @param  query string  Строка запроса urldecoded
 * @return boolean false
 */
function msQuery(mode, query='') {
  if (mode !== 'tableRename' && mode !== 'dbCreate'  && mode !== 'dbHide' && confirm('Подтвердите...') === false) {
    return false;
  }
  if (arguments.length === 0) {
    return false;
  }
  query = query.replace(/^\?/, '')

  // alert(query+'&'+'mode='+mode)
  query = query+'&'+'mode='+mode

  let ajaxdebug = (typeof(debug) != 'undefined' && debug === 1);

  let options = {
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
      if (text.indexOf('Parse error') !== -1) {
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

  return false;
}


/*
  getAllVars() {
    let sql = 'SHOW SESSION VARIABLES';
    let mode = 'querysql'
    let qs = querySql({sql, mode})
        .then((text) => {
          this.sessionVars = JSON.parse(text)
        })
        .then(this.getGlobals.bind(this));
    return qs;
  }
  */
function querySql(params, responseType='text', url='ajax.php') {

  return new Promise(function(resolve, reject) {
    let options = {
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    }
    if (params) {
      options.method = 'POST';
      if (params.tagName === 'FORM') {
        options.body = new FormData(params)
      } else {
        options.body = new URLSearchParams(params);
      }
    }

    fetch(url, options)
        .then(response => {
          if (!response.ok) {
            console.error(response.status +' ' + response.statusText)
          } else {
            return responseType === 'text' ? response.text() : response.json();
          }
        })
        .then(text => {
          resolve(text)
        })
        .catch((error) => {
          console.error(error)
        });
  });
}


/*async function apiQuery (query, options={}) {
  let url = 'http://msc/'
  options.method = 'GET'
  const response = await fetch(url, options)
  const json = await response.json();
  return json;
}*/


// umaker({db: 'xxx'})
function umaker(query={}, doSwitch=false) {
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
 * Отправляет форму form элемент по текущему урлу и возвращает json
 *
    update = (e) => {
        e.preventDefault()
        apiQuery(e.target.parentNode)
            .then(json => this.setState({messages: json.messages}))
    }

    GET запрос можно сделать через стандартный механизм проще
    restore = (e) => {
        fetch(umaker({action: 'restore', ajax: 1}))
          .then(response => response.json())
          .then(json => this.setState({messages: json.messages}))
    }
 * @param form
 * @returns {Promise<unknown>}
 */
function apiQuery(form) {
  // sendFormGetJson
  return querySql(form, 'json', umaker()+'&ajax=1');
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
 * Групповые действия со множественным селектором (select multiply)
 *
 * @param formName string Аттрибут name формы
 * @param fieldName string Аттрибут name элемента SELECT
 * @param option string Опция обработки select|unselect|invert
 */
function msMultiSelect(formName, fieldName, option) {
    let opts = document.querySelectorAll('form[name="'+formName+'"] select[name="'+fieldName+'"] option');
    opts.forEach(function(opt) {
    if (option === 'select') {
      opt.selected = true;
    }
    if (option === 'unselect') {
      opt.selected = false;
    }
    if (option === 'invert') {
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


/*
function createFlyBlock (id, style) {
  if (!(element = document.getElementById(id))) {
    let element = document.createElement('div');
    document.body.appendChild(element);
    for (let i in style) {
      element.style[i] = style[i];
    }
  }
  element.id = id;
  return element;
}
// трассировка
function trace(txt) {
  let d = createFlyBlock('traceBlock', {
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
}*/







/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Пакет общих функций и библиотек для разработки приложений
 * @pack 13.03.2010
 */

/*$ = function (id) {
    if (id.indexOf('#') === 0) {
        return jQuery(id);
    }
    return document.getElementById(id);
}*/
get = function (id) {
  return document.getElementById(id);
}


/**
 * Копирует последний ряд таблицы вниз
 * @param  tableId string   id таблицы
 * @return object Вставленная строка
 */
function addRow(tableId, from='last', after=true) {
  var table = get(tableId);
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
  var tr2 = get('trNewId' + i);
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
  let objSibling = get(sAfterId);
  objElement = document.createElement(sTag);
  objElement.setAttribute('id',sId);
  objSibling.parentNode.insertBefore(objElement, objSibling.nextSibling);
}
function insertBefore (sAfterId, sTag, sId){
  let objSibling = get(sAfterId);
  objElement = document.createElement(sTag);
  objElement.setAttribute('id',sId);
  objSibling.parentNode.insertBefore(objElement, objSibling);
}

/**
 * Удаляет ряд таблицы с конца
 */
function removeRow(tableId) {
  let r = get(tableId).rows;
  if (r.length === 1) {
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
  return (typeof(v) == 'undefined');
}
function trim(s) {
  s = s.replace(/[\s\t\r\n]+$/, '')
  return s.replace(/^[\s\t\r\n]+/, '')
}
function str_replace(srch, repl, str) {
  while (str.indexOf(srch) !== -1) {
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
    id = get(id);
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
function chbx_action(form_name, action, mask=false) {

  var add = '';
  if (mask) {
    add = '[name="'+mask+'"]'
  }
  var chbxs = document.querySelectorAll('form[name="'+form_name+'"] input[type="checkbox"]'+add);
  for (var chx of chbxs) {
    if (action == 'invert') {
      chx.checked = !chx.checked;
    } else if (action == 'check') {
      chx.checked = true;
    } else if (action == 'uncheck') {
      chx.checked = false;
    }
  }
  /*return ;

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
}*/
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

formatSize = (bytes, digits = 2) => {
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

function mysqlCenterInit() {

  $(document).ready(function () {

    hideTimeout = null;
    $('#appNameId').mouseover(function () {
      $('#dbHiddenMenu').show();
    })
    function menuHidder(e) {
      var w = parseInt($('#dbHiddenMenu').width());
      if (e.pageX > w) {
        hideTimeout = setTimeout(function() {
          $('#dbHiddenMenu').hide()
        }, 300);
      }
    }

    $('#dbHiddenMenu').mouseout(menuHidder);

    $('#dbHiddenMenu').mouseover(function (e) {
      if (hideTimeout != null) {
        clearInterval(hideTimeout);
      }
    })
    $('#dbHiddenMenu').on('click', function (e) {
      $('#dbHiddenMenu').hide();
    })
    $('#queryPopupBlock').hide()



    // Определяет активность клавиши CTRL
    window.globalCtrlKeyMode = false;
    window.key = {
      needkey:function(e) {
        var code;
        if (!e) var e = window.event;
        if (e.keyCode) code = e.keyCode;
        else if (e.which) code = e.which;
        if (globalCtrlKeyMode == true) {
          globalCtrlKeyMode = false;
        }
        if (e.ctrlKey == true && e.type == 'keydown') {
          globalCtrlKeyMode = true;
        }
      }
    }
    if (document.getElementById) {
      document.onkeydown = key.needkey;
      document.onkeyup = key.needkey;
    }

  });


// Мультиселектор чекбоксов. Указать индекс чекбокса и селектор элемента где он находится
// <input name="table[]" type="checkbox" value="1" onclick="checkboxer(5, '#row');">
  window.globalCheckboxLastIndex = null;
}

function checkboxer(index, selector) {
  if (globalCheckboxLastIndex == null) {
    globalCheckboxLastIndex = index;
    //return true;
  } else if (globalCtrlKeyMode && index != globalCheckboxLastIndex) {
    var from = globalCheckboxLastIndex > index ? index : globalCheckboxLastIndex;
    var to = globalCheckboxLastIndex > index ? globalCheckboxLastIndex : index;
    // Добавляем класс если надо
    var addClass = null;
    if (jQuery(selector+from).hasClass('selectedRow') || jQuery(selector+to).hasClass('selectedRow')) {
      var addClass = 'selectedRow';
    }
    for (var i = from; i <= to; i ++) {
      var o = jQuery(selector+i+' input');
      o.attr('checked', true);
      if (addClass != null) {
        jQuery(selector+i).addClass(addClass);
      }
    }
  }
}

function wordwrap (str, intWidth, strBreak, cut) {
  //  discuss at: https://locutus.io/php/wordwrap/
  // original by: Jonas Raoni Soares Silva (https://www.jsfromhell.com)
  // improved by: Nick Callen
  // improved by: Kevin van Zonneveld (https://kvz.io)
  // improved by: Sakimori
  //  revised by: Jonas Raoni Soares Silva (https://www.jsfromhell.com)
  // bugfixed by: Michael Grier
  // bugfixed by: Feras ALHAEK
  // improved by: Rafał Kukawski (https://kukawski.net)
  //   example 1: wordwrap('Kevin van Zonneveld', 6, '|', true)
  //   returns 1: 'Kevin|van|Zonnev|eld'
  //   example 2: wordwrap('The quick brown fox jumped over the lazy dog.', 20, '<br />\n')
  //   returns 2: 'The quick brown fox<br />\njumped over the lazy<br />\ndog.'
  //   example 3: wordwrap('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.')
  //   returns 3: 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod\ntempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim\nveniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea\ncommodo consequat.'
  intWidth = arguments.length >= 2 ? +intWidth : 75
  strBreak = arguments.length >= 3 ? '' + strBreak : '\n'
  cut = arguments.length >= 4 ? !!cut : false
  let i, j, line
  str += ''
  if (intWidth < 1) {
    return str
  }
  const reLineBreaks = /\r\n|\n|\r/
  const reBeginningUntilFirstWhitespace = /^\S*/
  const reLastCharsWithOptionalTrailingWhitespace = /\S*(\s)?$/
  const lines = str.split(reLineBreaks)
  const l = lines.length
  let match
  // for each line of text
  for (i = 0; i < l; lines[i++] += line) {
    line = lines[i]
    lines[i] = ''
    while (line.length > intWidth) {
      // get slice of length one char above limit
      const slice = line.slice(0, intWidth + 1)
      // remove leading whitespace from rest of line to parse
      let ltrim = 0
      // remove trailing whitespace from new line content
      let rtrim = 0
      match = slice.match(reLastCharsWithOptionalTrailingWhitespace)
      // if the slice ends with whitespace
      if (match[1]) {
        // then perfect moment to cut the line
        j = intWidth
        ltrim = 1
      } else {
        // otherwise cut at previous whitespace
        j = slice.length - match[0].length
        if (j) {
          rtrim = 1
        }
        // but if there is no previous whitespace
        // and cut is forced
        // cut just at the defined limit
        if (!j && cut && intWidth) {
          j = intWidth
        }
        // if cut wasn't forced
        // cut at next possible whitespace after the limit
        if (!j) {
          const charsUntilNextWhitespace = (line.slice(intWidth).match(reBeginningUntilFirstWhitespace) || [''])[0]
          j = slice.length + charsUntilNextWhitespace.length
        }
      }
      lines[i] += line.slice(0, j - rtrim)
      line = line.slice(j + ltrim)
      lines[i] += line.length ? strBreak : ''
    }
  }
  return lines.join('\n')
}

function htmlspecialchars(text) {
  var map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };

  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
