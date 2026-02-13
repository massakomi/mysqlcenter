import {HtmlSelector} from "../components";
import React, {useEffect, Fragment} from 'react';
import {
    checkboxAction,
    contentTableEvents, empty,
    forElementsEvent, GET, getElementIndex,
    isNumeric, msFormQuery,
    msQuery,
    processRowValue,
    umaker
} from "../functions";
import {Table} from "../components/Table";

function TableHeader(props) {

    const order = (field, e) => {
        e.preventDefault()
        const form = document.querySelector('.search-top')
        if (props.order === field) {
            field += '-'
        }
        form.querySelector('[name="order"]').value = field
        form.submit()
    };

    /**
     * (для tbl_data и tbl_compare) Получить массив заголовков для таблицы данных. Заголовки для таблиц данных
     * формируются особым образом, с переносом.
     *
     * @package data view
     * @return array  Массив заголовков
     * @param fields
     * @param sortEnabled
     */
    const getTableHeaders = (fields, sortEnabled=true) => {
        let headers = [];
        Object.keys(fields).forEach((k) =>  {
            let v = fields[k]
            let link = v.Field;
            if (sortEnabled) {
                let cls = 'br'
                if (props.order && props.order.indexOf(v.Field) === 0) {
                    cls += ' current'
                }
                link = <a href="#" onClick={order.bind(this, v.Field)} key={k} className={cls}>{v.Field}</a>
            }
            headers.push(link)
        })
        return headers;
    };
    let tableClass = Object.keys(props.fieldsEx).length < 15 ? 'wide' : ''

    const headers = getTableHeaders(props.fieldsEx, !props.directSQL);
    return (
      <table className={`contentTable interlaced ${tableClass}`}>
          <thead>
          <tr valign="top">
              <th></th>
              <th></th>
              <th></th>
              {headers.map((h, k) =>
                <th key={k}>{h}</th>
              )}
          </tr>
          </thead>
          <tbody>
          {props.children}
          </tbody>
      </table>
    );
}

function TableData(props) {

    const deleteRow = (idRow, e) => {
        e.preventDefault()
        const tr = e.target.closest('tr')
        const u2 = umaker({s: 'tbl_data', row: idRow})
        const query = `${u2}&db=${props.db}&table=${props.table}`
        msQuery('deleteRow', query, () => {
            tr.remove()
        })
    };

    // определение уникального ид ряда
    const getRowId = (row) => {

        // Собираем массив имён полей, и также массив имён только ключевых полей
        let pk = [], mul = []
        Object.values(props.fields).map(function(v) {
            if (v.Key.indexOf('PRI') > -1) {
                pk.push(v.Field)
            }
            if (v.Key.indexOf('MUL') > -1) {
                mul.push(v.Field)
            }
        })

        let pkValues = []
        if (pk.length > 0) {
            for (let pkCurrent of pk) {
                if (!row.hasOwnProperty(pkCurrent)) {
                    console.log(`Hey! Ключевого поля ${pkCurrent} не найдено в таблице!?`, row)
                    continue;
                }
                if (row[pkCurrent] === null) {
                    pkValues.push(`${pkCurrent} IS NULL`)
                } else {
                    pkValues.push(`${pkCurrent}='${row[pkCurrent]}'`);
                }
            }
            // если нет pk ключей, берем простые ключи
        } else if (mul.length > 0) {
            for (let pkCurrent of mul) {
                if (!row.hasOwnProperty(pkCurrent)) {
                    console.log(`Hey! Ключевого поля ${pkCurrent} не найдено в таблице!?`, row)
                    continue;
                }
                if (row[pkCurrent] === null) {
                    pkValues.push(`${pkCurrent} IS NULL`)
                } else {
                    pkValues.push(`${pkCurrent}='${row[pkCurrent]}'`);
                }
            }
            // если ничего нет, берем числовые поля
        } else {
            for (let field in props.fields) {
                let info = props.fields[field]
                let pkCurrent = info.Field
                if (info.Type.indexOf('int') < 0) {
                    continue;
                }
                if (!row[pkCurrent]) {
                    continue;
                }
                if (row[pkCurrent] === null) {
                    pkValues.push(`${pkCurrent} IS NULL`)
                } else {
                    pkValues.push(`${pkCurrent}='${row[pkCurrent]}'`);
                }
            }
        }
        return encodeURIComponent(pkValues.join(' AND '));
    }

    // fields for header
    let fields = props.fields;
    if (props.directSQL) {
        fields = []
        for (let field in props.data[0]) {
            let a = {}
            a.Field = field;
            a.Type = 'varchar'
            if (isNumeric(props.data[0][field])) {
                a.Type = 'int'
            }
            fields.push(a)
        }
    }

    // Собираем ряды
    let trs = []
    let rowNumber = 0
    for (let row of props.data) {

        let idRow = getRowId(row)

        // создание ссылок на действия
        let u1 = umaker({s: 'tbl_change', row: idRow});
        let values = [
            <input name="row[]" type="checkbox" value={idRow} className="cb" />,
            <a href={u1} title="Редактировать ряд или ctrl + click по ячейке для редактирования на месте"><img src={`${props.dirImage}edit.gif`} alt="" /></a>,
            <a href="#" onClick={deleteRow.bind(this, idRow)} title="Удалить ряд"><img src={`${props.dirImage}close.png`} alt="" /></a>
        ]

        // загрузка данных
        let cellNumber = 0
        for (let index in row) {
            let type = "varchar";
            if (fields[cellNumber]) {
                type = fields[cellNumber].Type
            }
            let val = processRowValue(row[index], type, props.textCut)
            if (val === 'null') {
                val = <span className="hiddenText">{val}</span>
            }
            values.push(val)
            cellNumber ++
        }

        let tds = [];
        let z = 0;
        for (let value of values) {
            tds.push(<td key={z}>{value}</td>)
            z ++;
        }
        trs.push(
          <tr key={rowNumber}>{tds}</tr>
        )
        rowNumber ++
    }

    return (
      <TableHeader {...props} fieldsEx={fields}>
          {trs}
      </TableHeader>
    );
}

function TableLinks(props) {

    const parts = () => {
        return [30, 50, 100, 200, 300, 500, 1000, 'all'];
    }

    function currentPart() {
        return GET('part', window.post.part)
    }

    const onChangePart = (e) => {
        let part = e.target.options[e.target.selectedIndex].value
        const form = document.querySelector('.search-top')
        form.querySelector('[name="part"]').value = part
        form.submit()
    }

    const onChangePage = (page, e) => {
        e.preventDefault()
        const form = document.querySelector('.search-top')
        form.querySelector('[name="go"]').value = page
        form.submit()
    }

    const collectPages = function* () {
        const countPages = Math.ceil(count / part)
        const currentPage = Math.ceil(getGo / part)
        const beginPage = Math.max(0, currentPage - Math.round(linksRange / 2))
        const endPage = Math.min(countPages, currentPage + Math.round(linksRange / 2))

        let pages = []
        for (let i = beginPage; i < endPage; i ++) {
            pages.push(i)
        }
        if (beginPage > 1) {
            pages.unshift(0)
        }
        if (countPages + 10 > endPage && countPages !== endPage) {
            pages.push(countPages - 1)
        }
        for (let i of pages) {
            yield i
        }
    }

    let getPart = currentPart()
    let getGo = props.go;
    let linksRange = props.linksRange;

    let count = props.count
    let part = props.part
    if (!part || count <= part && !getPart) {
        return false;
    }

    let links = []
    for (let i of collectPages()) {
        let cls = null
        if (getGo === i * part) {
            cls = 'cur'
        }
        links.push(<a key={i} href="#" onClick={onChangePage.bind(this, i * part)} className={cls}>{i + 1}</a>)
    }

    return (
      <div className="contentPageLinks">
          {links}
          <HtmlSelector data={parts()} auto="true" onChange={onChangePart} value={getPart} name="part" />
      </div>
    );
}

export function Tbl_data(props) {

    const chbx_action = (opt, e) => {
        e.preventDefault()
        checkboxAction('formTableRows', opt, 'cond[]')
    }

    const submitForm = (action, page, event) => {
        event.preventDefault()
        let form = event.target.closest('form')
        form.setAttribute('action', umaker({'s': page}))
        form.querySelector('[name="action"]').value = action
        form.submit()
    }

    if (empty(props)) {
        return ''
    }
    if (props.onlyData) {
        // Вывод explain
        if (Array.isArray(props.data) && props.data.length > 0 && props.data[0].hasOwnProperty('QUERY PLAN')) {
            return <QueryPlanDisplay data={props.data} />
        }
        return <Table data={props.data} />
    }

    useEffect(() => {

        contentTableEvents()

        forElementsEvent('click', '.contentTable td', function(e) {
            let tr = this.parentNode;
            let ch = tr.querySelector('input').checked;
            if (e.target.tagName === 'INPUT') {
                tr.classList.toggle('selectedRow', ch)
                return true;
            }
            tr.classList.toggle('selectedRow', !ch)
            tr.querySelector('input').checked = !ch
        })

        forElementsEvent('dblclick', '.contentTable tr', function() {
            location.href = this.querySelector('a').getAttribute('href');
        })

        // inline редактирование значений (пока сделано только вот это)
        forElementsEvent('click', '.contentTable td', function() {
            let index = getElementIndex(this)
            if (index <= 2 || !globalCtrlKeyMode) {
                return true;
            }
            let fields = Object.keys(props.fields)
            let column = fields[index - 3]
            let rowId = this.closest('tr').querySelector('[type="checkbox"]').value
            rowId = decodeURIComponent(rowId)
            let value = this.innerHTML
            this.innerHTML = `<input type="text" value="${value}" id="editable" />`
            let input = document.getElementById('editable')
            input.focus()
            input.addEventListener('focusout', function() {
                this.parentNode.innerHTML = this.value
                let table = '`'+window.table+'`'
                let sql = `UPDATE ${table} SET ${column}="${this.value}" WHERE ${rowId}`
                msQuery('querysql', {sql})
            })
        })
    }, []);

    const image = src => props.dirImage + src;

    let links = <TableLinks count={props.count} go={props.go} linksRange={props.linksRange} part={props.part} />

    return (
      <Fragment>
          <form action={props.url.replace('#s#', 'tbl_data')} method="post" name="formTableRows" id="formTableRows">
              <input type="hidden" name="rowMulty" value="1" />
              <input type="hidden" name="action" value="" />

              {links}
              <TableData {...props} />
              {links}

              <div className="flex baseline">
                  <div className="chbxAction mt-10 mb-10">
                      <img src={image("arrow_ltr.png")} alt="" align="absmiddle"/>
                      <a href="#" onClick={chbx_action.bind(this, 'check')}>выбрать все</a>  &nbsp;
                      <a href="#" onClick={chbx_action.bind(this, 'uncheck')}>очистить</a>
                  </div>

                  <div className="imageAction mb-10">
                      <u>Выбранные</u>
                      <input type="image" src={image("edit.gif")} alt="" onClick={submitForm.bind(this, 'editRows', 'tbl_change')}/>
                      <input type="image" src={image("close.png")} alt="" onClick={msFormQuery.bind(this, 'deleteRows')}/>
                      <input type="image" src={image("copy.gif")} alt="" onClick={msFormQuery.bind(this, 'copyRows')}/>
                      <input type="image" src={image("b_tblexport.png")} alt="" onClick={submitForm.bind(this, 'exportRows', 'export')}/>
                  </div>
              </div>

          </form>

          {props.dbs.length > 1 && props.showTableCompare ? <form name="form1" method="post" action={props.url.replace('#s#', 'tbl_compare')} className="mb-10">
              <input type="hidden" name="table[]" value={props.table}/>
              Сравнить таблицу с такой же в &nbsp;
              <HtmlSelector data={props.dbs} name="database"/> &nbsp;
              <input type="submit" value="Сравнить"/>
          </form> : null}

      </Fragment>
    );
}


function QueryPlanDisplay(props) {
    const queryPlanText = props.data
      .map(item => item['QUERY PLAN'])
      .filter(plan => plan !== null && plan !== undefined)
      .join('\r\n');
    return <textarea readOnly style={{width: '100%', minHeight: '400px', fontFamily: 'monospace'}} value={queryPlanText} />
}