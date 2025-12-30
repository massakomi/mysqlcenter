import React, {useState, useEffect, Fragment} from 'react';
import {HtmlSelector} from "../js/components";
import {list} from "../js/MysqlCenter";

function FormBottom() {
    return (
      <Fragment>
          <br /> <br /> после вставки
          <input name="redirect" type="radio" id="f2" value="tbl_data" defaultChecked/> <label htmlFor="f2">обзор таблицы</label>
          <input name="redirect" type="radio" id="f3" value="tbl_list"/> <label htmlFor="f3">список таблиц</label>
          <input name="redirect" type="radio" id="f4" value="tbl_change"/> <label htmlFor="f4">вставить новую запись</label>
          <br /><br />
          <input tabIndex="100" type="submit" value="Сохранить" className="submit"/>
      </Fragment>
    )
}

function getInputSize(props) {
    let diffLength = props.diffLength
    let type = props.type
    let value = props.value

    let length = null;
    let a = type.match(/\(([0-9]+)\)/)
    if (a && a[1]) {
        length = parseInt(a[1]);
    }
    if (length === 1) {
        //return '<input name="row['.$i.'][]" type="checkbox" value="1" />';
    }

    let size
    if (diffLength) {
        if (length <= 15) {
            size = length
        } else if (length < 30) {
            size = Math.round(length / 1.2)
        } else {
            size = Math.round(length / 3)
        }
    } else {
        if (length <= 15) {
            size = 15
        } else if (length < 128) {
            size = 50
        } else {
            size = 80
        }
    }
    if (type === 'datetime') {
        size = 30
    }
    if (type === 'timestamp' && value != null) {
        size = 50;
    }
    return size
}

function MSC_InsertInput(props) {

    let i = props.i;
    let j = props.j;
    let value = props.value
    let type = props.type

    if (type.match(/enum/i)) {
        /*
          preg_match_all('~(\'|")(.*)(\'|")~iU', $type, $items);
          if (isset($items[2])) {
            array_unshift($items[2], '');
            foreach ($items[2] as $k => $v) {
              $items[2][$k] = preg_replace('~\s+~i', ' ', $v);
            }
            $value = preg_replace('~\s+~i', ' ', $value);
            $attr = str_replace('onkeyup', 'onchange', $attr);
            return plDrawSelector(
                $items[2],
                ' name="row['.$i.']['.$j.']"'.$attr,
                array_search($value, $items[2]),
                '',
                false
            );
          }
          */
        return 'todo'
    }

    if (type.match(/(text|blob)/i)) {
        let rows = 10
        if (value != null && value.length > 0) {
            rows = Math.round(value.length / 60)
            if (rows < 10) {
                rows = 10
            }
        }
        return <textarea name={`row[${j}][${i}]`} cols="70" rows={rows} defaultValue={value}></textarea>
    }

    let size = getInputSize(props)
    return <input name={`row[${j}][${i}]`} type="text" size={size} defaultValue={value} className="si" />
}


function AddRow(props) {
    let i = props.i;
    let j = props.j;
    let name = props.name
    let fields = props.fields
    let value = props.value || ''
    if (value === 'CURRENT_TIMESTAMP') {
        value = ''
    }

    let type = fields[name].Type.replace(',', ', ')
    let nullCheckbox = '&nbsp;';
    if (fields[name].Null) {
        let checked = false
        if (value === '' && fields[name].Null === 'YES') {
            checked = true;
        }
        nullCheckbox = <input name={`isNull[${j}][${i}]`} type="checkbox" value="1" defaultChecked={checked} />
    }

    let funcs = ['', 'md5']

    return (
      <tr>
          <td><b className="field">{name}</b><br />{type}</td>
          <td>{nullCheckbox}</td>
          <td><MSC_InsertInput {...props} type={type} /></td>
          <td><HtmlSelector data={funcs} name={`func[${j}][${i}]`} /></td>
      </tr>
    )
}


function AddRows(props) {

    const [msRowsInsert, setMsRowsInsert] = useState(props.msRowsInsert);

    const addDataRow = inc => {
        setMsRowsInsert(msRowsInsert + inc)
        refreshActions();
    };


    let outerRows = []
    for (let j = 0; j < msRowsInsert; j++) {
        let tableInnerRows = Object.values(props.fields).map((field, i) =>
          <AddRow key={field.Field} name={field.Field} i={i} j={j} fields={props.fields} />
        );
        let tableInner = (
          <table style={{marginBottom: '10px'}}>
              <tbody>
              <tr className="editHeader">
                  <td>Поле</td>
                  <td>Ноль</td>
                  <td>Ряд #<span>{j + 1}</span></td>
                  <td>Функция</td>
              </tr>
              {tableInnerRows}
              </tbody>
          </table>)
        outerRows.push(<tr key={"row-"+j}><td className="inner">{tableInner}</td></tr>)
    }

    return (
      <form method="post" name="rowsForm" id="rowsForm" className="tableFormEdit">
          <input type="hidden" name="action" value="rowsAdd" />
          <img src={`${props.dirImage}nolines_plus.gif`} alt="" border="0" onClick={addDataRow.bind(this, 1)} title="Добавить поле" style={{cursor: 'pointer'}} />
          <img src={`${props.dirImage}nolines_minus.gif`} alt="" border="0" onClick={addDataRow.bind(this, -1)} title="Удалить поле" style={{cursor: 'pointer'}} /><br />
          <table id="tableDataAdd">
              <tbody>
              {outerRows}
              </tbody>
          </table>
          <FormBottom />
      </form>
    );
}


function EditRows(props) {

    let outerRows = []
    for (let j = 0; j < props.tableData.length; j++) {
        let data = props.tableData[j]

        let pk = [], mul = []
        let tableInnerRows = Object.keys(data).map((field, i) => {
            let value = data[field]
            let key = props.fields[field].Key;
            if (key.indexOf('PRI') > -1) {
                pk.push(`${field}='${value}'`)
            }
            if (key.indexOf('MUL') > -1) {
                mul.push(`${field}='${value}'`)
            }
            return <AddRow key={'row'+i} name={field} fields={props.fields} value={value} i={i} j={j} />
        });

        let cond = '';
        if (pk.length > 0) {
            cond = pk.join(' AND ')
        } else if (mul.length > 0) {
            cond = mul.join(' AND ')
        }
        let hiddenInput = (<input name="cond[]" type="hidden" value={cond} />)

        let tableInner = (
          <Fragment key={"row-"+j}>{hiddenInput}
              <table style={{marginBottom: '10px'}}>
                  <tbody>
                  <tr className="editHeader">
                      <td>Поле</td>
                      <td>Ноль</td>
                      <td>Ряд #<span>{j + 1}</span></td>
                      <td>Функция </td>
                  </tr>
                  {tableInnerRows}
                  </tbody>
              </table>
          </Fragment>)
        outerRows.push(tableInner)
        j ++
    }

    return (
      <form method="post" name="rowsForm" className="tableFormEdit" id="rowsForm">
          <input type="hidden" name="action" value="rowsEdit"/>
          {outerRows}

          <label htmlFor="f3"><input name="option" type="radio" value="save" id="f3" defaultChecked/> сохранить</label>
          <br/> или <br/>
          <label htmlFor="f4"><input name="option" type="radio" value="insert" id="f4"/> вставить новый ряд</label>
            <FormBottom />
      </form>
    );
}


export function Tbl_change() {

    useEffect(() => {
        refreshActions()
    }, []);

    if (options.redirect) {
        setTimeout(function () {
            location.href = options.redirect
        }, 2000);
    }

    return (
      <Fragment>
          {options.isAdd ? <AddRows {...options} /> : <EditRows {...options} />}
      </Fragment>
    );
}

/**
 * Назначает события функций processNullInput / processNull
 */
function refreshActions() {
    const inputs = document.getElementById('rowsForm').getElementsByTagName('INPUT')
    for (let i = 0; i < inputs.length; i++) {
        if (inputs[i].type === 'checkbox') {
            let textInput = inputs[i].closest('tr').querySelector('[type="text"], textarea')
            if (!textInput) {
                console.log('textInput not found')
                continue
            }
            list(textInput, 'keyup', function () {
                if (this.value !== '') {
                    inputs[i].checked = false
                }
            });
            list(inputs[i], 'click', function () {
                if (this.checked) {
                    textInput.value = ''
                }
            });
        }
    }
}
