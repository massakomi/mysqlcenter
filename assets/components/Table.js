import React, {useEffect} from "react";
import {contentTableEvents} from "../functions";

export function Table(props) {

    useEffect(() => {
        contentTableEvents()
    })

    // Скалярное представление значений
    const getValue = (item) => {
        let value = ''
        if (item === null) {
            value = <span className="hiddenText">null</span>
        } else if (typeof (item) == 'boolean') {
            let className = item ? 'success-light' : 'warning-light'
            value = <span className={className}>{item.toString()}</span>
        } else if (typeof (item) == 'object') {
            // item может быть объектом с полями text, href для создания ссылки
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
        return value
    }

    // Более общий метод
    const getValueTag = (row, key) => {
        let value = []
        if (key === 'act') {
            if (props.onDelete) {
                value.push(<a href="#" onClick={props.onDelete.bind(this, row)} title="Удалить"><img alt="" border="0" src="/images/close.png"/></a>)
            }
            if (props.onEdit) {
                value.push(<a href="#" onClick={props.onEdit.bind(this, row)} title="Редактировать"><img alt="" border="0" src="/images/edit.gif"/></a>)
            }
        } else {
            value = getValue(row[key])
        }
        return value
    }

    const getClass = (item) => {
        if (typeof (item) == 'object' && item !== null) {
            if (item.hasOwnProperty('class')) {
                return item['class']
            }
        }
        return null
    }

    const getTitle = (item) => {
        if (typeof (item) == 'object' && item !== null) {
            if (item.hasOwnProperty('title')) {
                return item['title']
            }
        }
        return null
    }

    if (!Array.isArray(props.data)) {
        return <div>data не массив</div>
    }

    if (props.data[0] == null || typeof (props.data[0]) == 'undefined') {
        return
    }

    let headers = Object.keys(props.data[0])
    if (props.onDelete || props.onEdit) {
        headers.unshift('act')
    }

    let ths = []
    for (let key of headers) {
        ths.push(<th key={`th-${key}`}><span className="br">{key}</span></th>)
    }

    let trs = []
    for (let index in props.data) {
        let tds = []
        const row = props.data[index]
        for (let key of headers) {
            let value = getValueTag(row, key)
            tds.push(<td key={`td-${key}`} title={getTitle(row[key])} className={getClass(row[key])}>{value}</td>)
        }
        trs.push(<tr key={`tr-${index}`}>{tds}</tr>)
    }

    return (
      <div className="responsive">
          <table className={`contentTable wide ${props.className}`}>
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