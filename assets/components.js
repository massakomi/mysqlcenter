import React, {useEffect} from 'react'
import {empty, forElementsEvent} from "./functions";

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
        if (props.auto && props.auto !== 'false') {
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
        opts.push(<option defaultValue={value} key={i++}>{title}</option>)
    }

    let value = props.value
    if (typeof(value) == 'undefined' || typeof(value) == 'object') {
        value = props.multiple ? [''] : ''
    }

    return (
      <select onChange={props.onChange ? props.onChange : onChange.bind(this)}
              name={props.name}
              multiple={props.multiple}
              defaultValue={value}
              className={props.className}>{opts}
      </select>
    )

}

// Сообщения, отображаемые при загрузке страницы под заголовком
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
        }
        if (item.type === 'error' && item.error !== '' && item.error != null) {
            extra.push(<div key="v3" className="mysqlError"><b>Ошибка:</b> {item.error}</div>)
        }
        let textClass = null
        if (item.text.startsWith('#0')) {
          textClass = 'pre'
        }
        return (
          <div style={{ color: item.color }}>
            <span className={textClass}>{item.text}</span>
            {extra}
        </div>)
    }

    const CloseMessage = (e) => {
        let block = e.target.closest('div').nextElementSibling
        e.target.innerHTML = block.hidden ? 'hide' : 'show'
        block.toggleAttribute('hidden')
    }
    if (empty(props.messages)) {
        return null
    }

    let messagesHide = window.messagesHide && props.messages.length > 1
    let text = messagesHide === 1  ? 'show' : 'hide'
    return <div className="messages">
        <div className="globalMessage" key={key}>
            <div>Сообщение <a href="#" className="hiddenSmallLink" style={{ color: 'white' }} onClick={CloseMessage.bind(this)}>{text}</a></div>
            <div hidden={messagesHide === 1}>{props.messages.map((item) =>
              MessageText(item)
            )}</div>
        </div>
    </div>
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
        if (!this.value && this.dataset['default']) {
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
    let isPost = !empty(post) ? '1' : ''
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

export function NotFound() {
    return (
      <div>
          <br />
        Компонент не найден, см. консоль
      </div>
    );
}