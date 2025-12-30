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

    const headers = getTableHeaders(props.fieldsEx, !props.directSQL, props.headWrap);
    return (
      <table className="contentTable interlaced">
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

function Table(props) {

    const deleteRow = (idRow, e) => {
        e.preventDefault()
        const tr = e.target.closest('tr')
        const u2 = umaker({s: 'tbl_data', row: idRow})
        const query = `${u2}&db=${props.db}&table=${props.table}`
        msQuery('deleteRow', query, () => {
            tr.remove()
        })
    };

     // Собираем массив имён полей, и также массив имён только ключевых полей
    let pk = [], mul = [], fieldNames = []
    Object.values(props.fields).map(function(v) {
        fieldNames.push(v.Field)
        if (v.Key.indexOf('PRI') > -1) {
            pk.push(v.Field)
        }
        if (v.Key.indexOf('MUL') > -1) {
            mul.push(v.Field)
        }
    })

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
    let j = 0
    for (let row of props.data) {

        // определение уникального ид ряда
        let pkValues = []
        if (pk.length > 0) {
            for (let pkCurrent of pk) {
                if (!row[pkCurrent]) {
                    console.log(`Hey! Ключевого поля ${pkCurrent} не найдено в таблице!?`)
                    continue;
                }
                pkValues.push(`${pkCurrent}='${row[pkCurrent]}'`);
            }
            // если нет pk ключей, берем простые ключи
        } else if (mul.length > 0) {
            for (let pkCurrent of mul) {
                if (!row[pkCurrent]) {
                    console.log(`Hey! Ключевого поля ${pkCurrent} не найдено в таблице!?`)
                    continue;
                }
                pkValues.push(`${pkCurrent}='${row[pkCurrent]}'`);
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
                pkValues.push(`${pkCurrent}='${row[pkCurrent]}'`);
            }
        }
        let idRow = encodeURIComponent(pkValues.join(' AND '));

        // создание ссылок на действия
        let u1 = umaker({s: 'tbl_change', row: idRow});
        let values = [
            <input name="row[]" type="checkbox" value={idRow} className="cb" />,
            <a href={u1} title="Редактировать ряд"><img src={`${props.dirImage}edit.gif`} alt="" border="0" /></a>,
            <a href="#" onClick={deleteRow.bind(this, idRow)} title="Удалить ряд"><img src={`${props.dirImage}close.png`} alt="" border="0" /></a>
        ]

        // загрузка данных
        let i = 0
        for (let k in row) {
            let type = "varchar";
            if (fields[i]) {
                type = fields[i].Type
            }
            let val = processRowValue(row[k], type, props.textCut)
            if (val === 'null') {
                val = <span className="hiddenText">{val}</span>
            }
            values.push(val)
            i ++
        }

        let tds = [];
        let z = 0;
        for (let value of values) {
            tds.push(<td key={z}>{value}</td>)
            z ++;
        }
        trs.push(
          <tr key={j}>{tds}</tr>
        )
        j ++
    }

    return (
      <TableHeader {...props} fieldsEx={fields}>
          {trs}
      </TableHeader>
    );
}

function TableLinks(props) {

    const pagesObj = () => {
        let pagesNums = [30, 50, 100, 200, 300, 500, 1000, 'all']
        let pages = {}
        let u = new URL(location.href)
        for (let num of pagesNums) {
            u.searchParams.set('part', num)
            pages[u.href] = num
        }
        return pages;
    };

    const page = (page, e) => {
        e.preventDefault()
        const form = document.querySelector('.search-top')
        form.querySelector('[name="go"]').value = page
        form.submit()
    };


    let getPart = new URL(location.href).searchParams.get('part');
    let getGo = props.go;
    let linksRange = props.linksRange;

    let count = props.count
    let part = props.part
    if (!part || count <= part && !getPart) {
        return false;
    }

    let links = []
    let countPages = Math.ceil(count / part)
    let currentPage = Math.ceil(getGo / part)
    let beginPage = Math.max(0, currentPage - linksRange)
    let endPage = Math.min(countPages, currentPage + linksRange)

    for (let i = beginPage; i < endPage; i ++) {
        if (getGo == i * part) {
            links.push(<a key={i} href="#" onClick={page.bind(this, i * part)} className="cur">{i + 1}</a>)
        } else {
            links.push(<a key={i} href="#" onClick={page.bind(this, i * part)}>{i + 1}</a>)
        }
    }

    let data = pagesObj();

    let selected = false;
    if (getPart) {
        for (let key in data) {
            if (data[key] == getPart) {
                selected = key
            }
        }
    }

    return (
      <div className="contentPageLinks">
          {links}
          <HtmlSelector data={data} auto="true" value={selected} keyValues="true" />
      </div>
    );
}

function Tbl_data(props) {

    const checkboxAction = (opt, e) => {
        e.preventDefault()
        checkboxAction('formTableRows', opt, 'row[]')
    }

    const imageAction = (opt, url, e) => {
        if (url) {
            url = props.url.replace('#s#', url)
        }
        msImageAction('formTableRows', opt, url)
    }

    React.useEffect(() => {

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

        forElementsEvent('dblclick', '.contentTable tr', function(e) {
            location.href = this.querySelector('a').getAttribute('href');
        })

        // todo реализовать inline редактирование значений (пока сделано только вот это)
        forElementsEvent('click', '.contentTable td', function(e) {
            if (getElementIndex(this) <= 2) {
                return true;
            }
            if (globalCtrlKeyMode) {
                let value = this.innerHTML
                this.innerHTML = `<input type="text" value="${value}" id="editable" />`
                document.getElementById('editable').focus()
                document.getElementById('editable').addEventListener('focusout', function() {
                    this.parentNode.innerHTML = this.value
                })
            }
            return true;
        })
    }, []);

    if (Object.keys(props).length === 0) {
        return ''
    }

    const image = src => props.dirImage + src;

    let links = <TableLinks count={props.count} go={props.go} linksRange={props.linksRange} part={props.part} />

    return (
      <React.Fragment>
          <form action={props.url.replace('#s#', 'tbl_data')} method="post" name="formTableRows" id="formTableRows">
              <input type="hidden" name="rowMulty" value="1" />
              <input type="hidden" name="action" value="" />

              {links}
              <Table {...props} />
              {links}

              <div className="chbxAction">
                  <img src={image("arrow_ltr.png")} alt="" border="0" align="absmiddle" />
                  <a href="#" onClick={checkboxAction.bind(this, 'check')}>выбрать все</a>  &nbsp;
                  <a href="#" onClick={checkboxAction.bind(this, 'uncheck')}>очистить</a>
              </div>

              <div className="imageAction">
                  <u>Выбранные</u>
                  <img src={image("edit.gif")} alt="" border="0" onClick={imageAction.bind(this, 'editRows', 'tbl_change')} />
                  <img src={image("close.png")} alt="" border="0" onClick={imageAction.bind(this, 'deleteRows', '')} />
                  <img src={image("copy.gif")} alt="" border="0" onClick={imageAction.bind(this, 'copyRows', '')} />
                  <img src={image("b_tblexport.png")} alt="" border="0" onClick={imageAction.bind(this, 'exportRows', 'export')} />
              </div>
          </form>
          <form name="form1" method="post" action={props.url.replace('#s#', 'tbl_compare')}>
              <input type="hidden" name="table[]" value={props.table} />
              Сравнить таблицу с такой же в &nbsp;
              <HtmlSelector data={props.dbs} name="database" /> &nbsp;
              <input type="submit" value="Сравнить" />
          </form>
      </React.Fragment>
    );
}