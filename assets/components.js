import React from 'react'
import {Fragment, useEffect} from "react";
import {contentTableEvents, forElementsEvent, searchEvents} from "./functions";

export function CharsetSelector(props) {
    let opts = [], i = 0
    for (let charset in props.charsets) {
        let title = null
        let info = props.charsets[charset]
        if (typeof info == 'object') {
            title = info.Description + ' (default: ' + info['Default collation'] + ')'
        }
        opts.push(<option key={i++} title={title}>{charset}</option>)
    }

    return (
      <select name="charset" defaultValue={props.value ? props.value : 'utf8'}>{opts}</select>
    )
}

export function HtmlSelector(props) {

    let onChange = (e) => {
        if (props.auto) {
            let value = e.target.options[e.target.selectedIndex].value
            if (value) {
                window.location = value
            }
        }
    }

    let opts = [], i = 0
    for (let key in props.data) {
        let title = props.data[key]
        let value
        if (props.keyValues) {
            value = key
        } else {
            value = null
        }
        opts.push(<option value={value} key={i++}>{title}</option>)
    }

    return (
      <select onChange={props.onChange ? props.onChange : onChange.bind(this)}
              name={props.name}
              multiple={props.multiple}
              defaultValue={props.value}
              className={props.className}>{opts}
      </select>
    )

}

// Оставляю, жалко удалять, вдруг пригодится
export function Messages(props) {

    const MessageText = (item) => {
        let extra = []
        if (typeof(item) === 'string') {
            return (<div>{item}</div>)
        }
        if (item.sql) {
            extra.push(<div key="v1" className="sqlQuery">{item.sql}</div>)
            if (item.rows) {
                extra.push(<div key="v2" style={{ color: '#ccc' }}>затронуто рядов: {item.rows}</div>)
            }
            if (item.error !== '' && item.error != null) {
                extra.push(<div key="v3" className="mysqlError"><b>Ошибка:</b> {item.error}</div>)
            }
        }
        return (<div style={{ color: item.color }}>{item.text}{extra}</div>)
    }

    const CloseMessage = (e) => {
        e.target.closest('tr').nextElementSibling.toggleAttribute('hidden')
    }

    return <div className="messages">{props.messages.map((item, key) =>
      (
        <div className="globalMessage" key={key}>
            <div>Сообщение <a href="#" className="hiddenSmallLink" style={{ color: 'white' }}
                             onClick={CloseMessage.bind(this)}>close</a></div>
            <div>{MessageText(item)}</div>
        </div>
      )
    )}</div>
}


export function ExportOptions(props) {

    const image = (src) => {
        return props.dirImage + src
    }

    return (
      <div className="options">
          <div>
              <label htmlFor="f1"><input type="checkbox" value="1" name="export_struct" id="f1"
                                         defaultChecked={props.structChecked} /> Структура</label>

              <label htmlFor="f2" title="Укажите эту опцию, если вы хотите заменить таблицу (команда DROP TABLE)">
                  <input type="checkbox" value="1" className="l2" name="addDrop" id="f2" />
                  Добавить удаление таблицы
              </label>

              <label htmlFor="f3"
                     title="Будут преобразованы команды: CREATE TABLE IF NOT EXISTS... и при удалении таблиц DROP TABLE IF EXISTS ...">
                  <input type="checkbox" value="1" className="l2" name="addIfNot" id="f3" />
                  Добавить IF NOT EXISTS
              </label>

              <label htmlFor="f4" title="К каждой таблице будет добавлено AUTO_INCREMENT=текущее значение">
                  <input type="checkbox" value="1" className="l2" name="addAuto" id="f4" />
                  Добавить значение AUTO_INCREMENT
              </label>

              <label htmlFor="f5" title="Оставьте эту опцию, чтобы быть уверенным, что всё пройдет гладко">
                  <input type="checkbox" value="1" className="l2" name="addKav" id="f5" defaultChecked />
                  Обратные `кавычки` в названиях таблиц и полей
              </label>

              <label htmlFor="f12" title="Шапка к дампу с информацией о версиях ПО, а также заголовки таблиц">
                  <input type="checkbox" value="1" name="addComment" id="f12" defaultChecked />
                  Добавлять комментарии
              </label>

              <label htmlFor="f13"><input name="export_to" id="f13" type="radio" value="1" /> в архив</label>
              <label htmlFor="f14"><input name="export_to" id="f14" type="radio" value="2" defaultChecked /> в
                  текст</label>
          </div>
          <div>
              <label htmlFor="f6"><input type="checkbox" value="1" name="export_data" id="f6" defaultChecked />Данные</label>

              <label htmlFor="f7">
                  <input type="checkbox" value="1" className="l2" name="insFull" id="f7" defaultChecked />
                  Указать все поля <img src={image('i-help2.gif')}
                                        title="В запросе будут перечислены все поля INSERT INTO table (fields...) VALUES (...), иначе перечисление полей пропускается. Используейте эту опцию, если вы не уверены, что порядок полей сохранится."
                                        className="helpimg" alt="" />
              </label>

              <label htmlFor="f8">
                  <input type="checkbox" value="1" className="l2" name="insExpand" id="f8" />
                  Одним запросом <img src={image('i-help2.gif')}
                                      title="Все вставки будут осуществлены одним запросом вида INSERT INTO table VALUES (set1..), (set2...), (set3...) etc"
                                      className="helpimg" alt="" />
              </label>

              <label htmlFor="f9">
                  <input type="checkbox" value="1" className="l2" name="insZapazd" id="f9" />
                  DELAYED <img src={image('i-help2.gif')} title="DELAYED. Сервер сначала отправит запрос в буфер и если таблица используется, то вставку рядов приостановится. Когда таблица освободится, сервер начнёт выполнять запрос и вставлять строки, периодически проверяя, появились ли новые запросы к таблице. Если да, то вставка рядов будет снова приостановлена до того момента, как таблица снова освободится.
--- Эта опция полезна, когда немедленное обновление таблицы не требуется (например, при логах), а также если осуществляется множество запросов на вставку. Это даёт существенный прирост производительности при вставках и не задерживает обычную выборку. В то же время такие запросы медленнее обычных и вы должны быть уверенными, что они вам нужны."
                               className="helpimg" alt="" />
              </label>

              <label htmlFor="f10">
                  <input type="checkbox" value="1" className="l2" name="insIgnor" id="f10" />
                  IGNORE <img src={image('i-help2.gif')}
                              title="IGNORE. Ошибки, которые происходят при выполнении INSERT запроса игнорируются, то есть статус сообщения об ошибке меняется с ERROR на WARNING. С IGNORE, неправильные значения исправляются до ближайших валидных значений и вставляются, warning`и появляются, но выражение выполняется. Кроме этого в выражениях вида INSERT IGNORE INTO sdf (a,b) VALUES (6,6), (2,2), (7,7) будут вставлены все значения за исключением дублирующих. При отсутствии опции IGNORE будут вставлены все значения ДО дублирующих и выскочит ошибка."
                              alt="" className="helpimg" />
              </label>

              Тип экспорта
              <select name="export_option">
                  <option>INSERT</option>
                  <option>UPDATE</option>
                  <option
                    title="REPLACE работает точно так же, как INSERT, за исключением тех случаев, когда старая строка в таблице имеет те же значения, что и новая строка для полей с индексами PRIMARY KEY или UNIQUE. В этом случае старый ряд будет удалён перед вставкой нового ряда. REPLACE это собственное расширение MySQL. Он либо вставляет, либо удаляет и вставляет. Заметьте, что если таблица не имеет ключей PRIMARY KEY либо UNIQUE, то использование REPLACE не даст ничего. В этом случае он становится аналогичным INSERT">REPLACE
                  </option>
              </select>

              {props.fields &&
                <Fragment>
                    <div> Выбрать поля для экспорта:</div>
                    <HtmlSelector data={props.fields} name="fields[]" multiple="multiple"
                                  value={props.fields} />
                </Fragment>
              }
          </div>

      </div>
    )
}


export function Table(props) {

    useEffect(() => {
        contentTableEvents()
    })

    let ths = []
    for (let key in props.data[0]) {
        ths.push(<th key={`th-${key}`}><span className="br">{key}</span></th>)
    }

    let trs = []
    for (let index in props.data) {
        let tds = []
        for (let key in props.data[index]) {
            const item = props.data[index][key]
            let value = ''
            if (item !== null && typeof(item) == 'object') {
                if (item.hasOwnProperty('text')) {
                    value = item.text
                    if (item.href) {
                        value = <a href={item.href}>{value}</a>
                    }
                } else {
                    value = item.toString()
                }
            } else {
                value = item
            }
            tds.push(<td key={`td-${key}`}>{value}</td>)
        }
        trs.push(<tr key={`tr-${index}`}>{tds}</tr>)
    }

    return (
      <div className="responsive">
          <table className="contentTable">
              <thead>
              <tr>{ths}</tr>
              </thead>
              <tbody>
              {trs}
              </tbody>
          </table>
      </div>
    )
}

function SearchDbForm() {

    if (!window.db) {
        return null
    }

    function onFocus(e) {
        e.target.value = '';
        e.target.style.width = 'auto'
    }

    function onSubmit(e) {
        e.target.action = e.target.action + '&query=' + e.target.query.value
    }

    return (
      <form action={`?db=${window.db}&s=search`} method="post" onSubmit={onSubmit}>
          <input type="text" name="query" defaultValue="Поиск по базе" onFocus={onFocus}/>
      </form>
    );
}

function SearchTableFormEvents() {
    forElementsEvent('focus', '.search-top [name="query"]', function () {
        this.classList.add('wide')
        if (this.value.indexOf('Поиск') === 0) {
            this.dataset['default'] = this.value
            this.value = ''
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

function SearchTableForm(props) {
    if (!window.table) {
        return null
    }

    useEffect(() => {
        SearchTableFormEvents()
    }, []);

    let post = window.post || {}
    let isPost = Object.keys(post).length > 0 ? '1' : ''
    let query = post.query ? post.query : 'Поиск или where'

    const url = `?db=${window.db}&table=${window.table}&s=tbl_data`;
    return (
      <form action={url} method="post" className="search-top">
          <input type="hidden" name="post" defaultValue={isPost} />
          <input type="hidden" name="order" defaultValue={post.order} />
          <input type="hidden" name="go" defaultValue={post.go} />
          <input type="hidden" name="part" defaultValue={post.part} />
          <input type="text" name="query" defaultValue={query} />
          <HtmlSelector data={window.fields} name="field" value={post.field}/>
          <HtmlSelector data={['=', 'like']} name="like"  value={post.like} />
          <input type="text" name="byField" defaultValue={post.byField} />
          <input type="submit"/>
      </form>
    );
}

export function HeadTop(props) {

    return (
      <div className="headTop">
          <h1>{window.pageTitle}</h1>
          <div>
              <SearchTableForm/>
              <SearchDbForm/>
          </div>
      </div>
    )
}

export function NotFound(props) {
    return (
      <div>
        Компонент не найден
      </div>
    );
}