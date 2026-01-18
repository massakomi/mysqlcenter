import React, {Fragment} from 'react';
import {check, forElements, msFormQuery, msQuery, submitFormIfFieldNotEmpty} from "../functions";
import {MysqlKeysInfo} from "./mysql/keys";
import {PgKeysInfo} from "./pgsql/keys";

function TableObject(props) {
    // нулевой элемент раскидвать по ключ-значение, бред редко нужно
    let tableInfo = props.data[0]
    const listItems = []
    for (const key in tableInfo) {
        let title = key in props.columns ? props.columns[key] : ''
        listItems.push(
          <tr key={key+"index"} title={title}>
              <td>{key}</td>
              <td>{tableInfo[key]}</td>
          </tr>
        )
    }

    return (
      <table className="contentTable">
          <tbody>
          {listItems}
          </tbody>
      </table>
    )
}


function TableStruct(props) {

    const deleteField = (field, e) => {
        e.preventDefault()
        e.target.closest('tr').remove()
        let msquery = 'db='+props.db+'&table='+props.table+'&s=tbl_struct'
        let urlDelete = msquery + '&field=' + field
        let query = urlDelete + '&id=f-' + encodeURIComponent(field)
        msQuery('deleteField', query)
    };

    const getKey = (props, field) => {
        let key = field.Key;
        if (key === 'PRI') {
            key = <img src={props.dirImage + "acl.gif"} alt="" border="0" />
        }
        for (let keyObject of props.dataKeys) {
            if (keyObject.Column_name === field.Field) {
                let fkTable = '';
                for (let key of props.foreignKeys) {
                    if (key.COLUMN_NAME === field.Field) {
                        fkTable = key.REFERENCED_TABLE_NAME;
                    }
                }
                if (keyObject.Key_name.indexOf('FK') === 0 || fkTable) {
                    key = <span title={'Table: ' + (fkTable ? fkTable : keyObject.Table)}>FK</span>
                }
            }
        }
        return key
    }

    const getDefault = (field) => {
        let value = field.Default
        if (value && value.indexOf('nextval') > -1) {
            value = <span title={value}>SERIAL</span>
        }
        return value
    }

    const listItems = Object.values(props.data).map((field, k) => {
        let matches = field.Type.match(/\((\d+)\)/)
        if (matches) {
          field.Type = field.Type.replace(matches[0], '')
          field.Length = matches[1]
        }

        return (
          <tr id={"f-"+field.Field} key={field.Field}>
              <td><input name="field[]" id={"field"+k} type="checkbox" value={field.Field} className="cb" /></td>
              <td>{field.Field}</td>
              <td>{field.Type}</td>
              <td>{field.Length}</td>
              <td>{field.Null}</td>
              <td>{getDefault(field)}</td>
              <td>{getKey(props, field)}</td>
              <td>{field.Extra}</td>
              <td><a href={`?s=tbl_add&table=${props.table}&field=`+encodeURIComponent(field.Field)} title="Редактировать ряд"><img src={props.dirImage + "edit.gif"} alt="" /></a></td>
              <td><a href="#" onClick={deleteField.bind(this, field.Field)} title="Удалить ряд"><img src={props.dirImage + "close.png"} alt="" /></a></td>
          </tr>
        )
    });

    return (
      <table className="contentTable">
          <thead>
          <tr>
              <th>&nbsp;</th>
              <th>Поле</th>
              <th>Тип</th>
              <th>Length</th>
              <th>NULL</th>
              <th>По умолчанию</th>
              <th>Ключ</th>
              <th>Дополнительно</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
          </tr></thead>
          <tbody>
          {listItems}
          </tbody>
      </table>
    )
}

export function Tbl_struct(props) {

    const checkboxAction = (opt, event) => {
        event.preventDefault()
        forElements('#formTableStructure input[type="checkbox"]', function(e) {
            this.checked = opt === 'check'
        })
     };

    const onSubmit = (e, xx) => {
        e.preventDefault()
        submitFormIfFieldNotEmpty(e.target, 'fieldsNum')
    }

    const selectOnFocus = (e) => {
        document.getElementById('f3').checked = true
    }

    return (
      <Fragment>
          <div className="flex">
              <div>
                  <form action={props.addTableUrl} method="post" name="formTableStructure" id="formTableStructure">
                      <input type="hidden" name="action" value="fieldsEdit" />

                      <TableStruct {...props} />

                      <div className="flex baseline mt-10 mb-10">
                          <div className="chbxAction">
                              <img src={props.dirImage + "arrow_ltr.png"} alt="" border="0" align="absmiddle"/>
                              <a href="#" onClick={checkboxAction.bind(this, "check")}>выбрать все</a>  &nbsp;
                              <a href="#" onClick={checkboxAction.bind(this, "uncheck")}>очистить</a>
                          </div>

                          <div className="imageAction">
                              <u>Выбранные</u>
                              <input type="image" src={props.dirImage + "edit.gif"} alt=""/>
                              <input type="image" src={props.dirImage + "close.png"} onClick={msFormQuery.bind(this, 'fieldsDelete')} alt=""/>
                          </div>
                      </div>

                  </form>

                  <fieldset className="msGeneralForm">
                      <legend>Изменить структуру</legend>
                      <form action={props.addTableUrl} method="post" onSubmit={onSubmit}>
                          <input type="hidden" name="action" value="fieldsAdd"/>
                          Добавить полей &nbsp; <input name="fieldsNum" type="text" defaultValue="1" size="5"/> &nbsp;
                          <input name="afterOption" type="radio" value="end" defaultChecked id="f1"/> <label htmlFor="f1">в конец </label>
                          <input name="afterOption" type="radio" value="start" id="f2"/> <label htmlFor="f2">в начало</label>
                          <input name="afterOption" type="radio" value="field" id="f3"/> <label htmlFor="f3">после </label>
                          <select name="afterField" onFocus={selectOnFocus}>
                          {Object.values(props.data).map((table) =>
                                <option key={table.Field}>{table.Field}</option>
                              )}
                          </select>&nbsp;
                          <input type="submit" value="Добавить!" />
                      </form>
                  </fieldset>


              </div>
              <div style={{padding: '20px 0 0 10px'}}>
                  <strong> Подробности таблицы </strong>
                  <br />
                  <TableObject data={props.dataDetails[1]} columns={props.dataDetails[0]} />
              </div>
          </div>

          <h3>Информация о ключах</h3>

          {props.showKeys ?
            (window.driver === 'pgsql' ? <PgKeysInfo {...props} /> : <MysqlKeysInfo {...props} />) :
            <a href={props.showKeysUrl}>Показать информацию о ключах</a>}

          <p><a href={props.addKeyUrl}>Добавить ключ</a></p>

          <textarea className="wide" defaultValue={props.sqlCreateTable}></textarea>
      </Fragment>
    );
}