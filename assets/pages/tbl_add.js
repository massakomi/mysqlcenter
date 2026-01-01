import React, {Fragment, useEffect} from 'react';
import {addRow, empty, forElementsEvent, GET, msQuery, qs, umaker} from "../functions";

const getAction = () => {
    const s = GET('s')
    const field = GET('field')
    if (s === 'tbl_add' && empty(window.post) && !field) {
        return 'tableAddEnd';
    }
    if (window.post.action === 'fieldsAdd') {
        return 'fieldsAddEnd';
    }
    return 'fieldsEditEnd';
}

function TableHead(props) {
    return (
      <table id="tableFormEdit">
          <thead>
          <tr>
              <th>Поле</th>
              <th>Тип</th>
              <th>Длина/значения</th>
              <th>Ноль</th>
              <th>По умолчанию</th>
              <th>Au</th>
              <th>PR</th>
              <th>UN</th>
              <th>IND</th>
              <th>-</th>
              <th>FU</th>
              <th>Атрибуты</th>
              <th>После...</th>
          </tr></thead>
          <tbody>
          {props.children}
          </tbody>
      </table>
    );
}

function MSC_DrawFields(props) {

    const aiClick = k => {
        document.getElementById(`default${k}`).value = ''
    };

    const clearKeys = k => {
        document.getElementById('key1'+k).checked = false;
        document.getElementById('key2'+k).checked = false;
        document.getElementById('key3'+k).checked = false;
        return false;
    };

    // Оставить только те поля, которе редактируем
    const filterFields = (props) => {
        let edited = []
        const fieldGet = GET('field')
        if (fieldGet) {
            edited.push(fieldGet)
        }
        if (window.post.fields) {
            edited = window.post.fields.split(',')
        }
        if (window.post.field) {
            edited = window.post.field
        }
        fields = props.fields.filter(function(item) {
            return edited.includes(item.Field);
        });
        return fields
    }

    let POST = window.post || {};
    let keys = props.keys;
    let action = getAction()

    console.log(action, props)

    // получение массива "предыдущих полей" полей
    let fieldsAfter = ['FIRST']
    let previousFields = {}
    let prev = '';
    let fields
    if (action === 'tableAddEnd' || action === 'fieldsAddEnd') {
        fields = []
        for (let i = 0; i < props.fieldsCount; i ++) {
            fields.push(i)
        }
    } else {
        fields = filterFields(props);
        fields.map((field) => {
            previousFields[field.Field] = prev
            prev = field.Field
            fieldsAfter.push(field.Field)
        })
    }

    // создание селектора типов данных
    let columnTypes = [
        'VARCHAR', 'TINYINT', 'TEXT', 'DATE', 'JSON',
        'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT',
        'FLOAT', 'DOUBLE', 'DECIMAL',
        'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR',
        'CHAR', 'TINYBLOB', 'TINYTEXT', 'BLOB', 'MEDIUMBLOB', 'MEDIUMTEXT', 'LONGBLOB', 'LONGTEXT',
        'ENUM', 'SET', 'BOOLEAN', 'SERIAL'
    ];

    const trs = fields.map((field, index) => {

        let NAME='', TYPE='', LENGTH='', DEFAULT='', ISNULL='', AUT='', extra='';
        let isUnsignedZero=false, isUnsigned=false;
        let PRI='', UNI='', MUL='', uniName='', mulName='';

        if (typeof field == 'object') {
            let a = field.Type.match(/\((.*)\)/)
            if (a) {
                LENGTH = a[1]
            }
            isUnsignedZero = field.Type.match(/unsigned zerofill/i) !== null;
            isUnsigned = field.Type.match(/unsigned/i) !== null;
            NAME = field.Field;
            TYPE = field.Type.replace(/\((.*)\).*/i, '').toUpperCase();
            DEFAULT = field.Default;
            ISNULL = field.Null === true || field.Null === "YES";
            AUT = field.Extra !== ""
            extra = (
              <Fragment>
                  <select name="after[]" defaultValue={previousFields[field.Field]}>
                      {fieldsAfter.map((v) =>
                        <option key={v}>{v}</option>
                      )}
                  </select>
                  <input type="hidden" name="afterold[]" defaultValue={previousFields[field.Field] || 'FIRST'} />
              </Fragment>
            )
            if (keys[NAME]) {
                Object.keys(keys[NAME]).forEach(function(keyName) {
                    let key = keys[NAME][keyName];
                    PRI = key === "PRI" || POST.primaryKey === NAME
                    if (key === "UNI" || POST.uni && POST.uni[NAME]) {
                        UNI = true
                        uniName = keyName
                    }
                    if (key === "MUL" || POST.mul && POST.mul[NAME]) {
                        MUL = true
                        mulName = keyName
                    }
                });
            }

        } else if (POST.afterOption) {
            let checked = ''
            if (POST.afterOption === 'end') {
                checked = fieldsAfter[fieldsAfter.length - 1]
            } else if (POST.afterOption === 'field') {
                checked = POST.afterField
            }
            extra = (
              <Fragment>
                  <select name={`after[${index}]`} defaultValue={checked}>
                      {fieldsAfter.map((v) =>
                        <option key={v}>{v}</option>
                      )}
                  </select>
                  <input type="hidden" name={`afterold[${index}]`} defaultValue={checked} />
              </Fragment>
            )
        }

        let j = NAME === '' ? index : NAME;

        let attr = isUnsigned ? 'UNSIGNED' : (isUnsignedZero ? 'UNSIGNED ZEROFILL' : '');

        return (
          <tr key={index} id={`tableFormEditTr${index}`}>
              <td>
                  <input name="name[]" tabIndex="1" id={`name${index}`} type="text" defaultValue={NAME} size="15" />
                  <input type="hidden" name="oldname[]" defaultValue={NAME} />
              </td>
              <td>
                  <select name="ftype[]" tabIndex="2" id={`typeSelectorId${index}`} defaultValue={TYPE}>
                      {columnTypes.map((v) =>
                        <option key={v}>{v}</option>
                      )}
                  </select>
              </td>
              <td><input name="length[]" tabIndex="3" id={`length${index}`} type="text" defaultValue={LENGTH} size="30" /></td>
              <td><input name={`isNull[${index}]`} tabIndex="4" id={`isNull${index}`} type="checkbox" value="1" defaultChecked={ISNULL} /></td>
              <td><input name="default[]" tabIndex="5" id={`default${index}`} type="text" size="10" defaultValue={DEFAULT} /></td>
              <td><input name={`auto[${index}]`} tabIndex="6" id={`auto${index}`} onClick={aiClick.bind(this, index)} type="checkbox" value="1" defaultChecked={AUT}  /></td>
              <td><input name="primaryKey" tabIndex="7" id={`key1${index}`} type="radio" defaultValue={j} defaultChecked={PRI} /></td>
              <td><input name={`uni[${j}]`} tabIndex="8" id={`key2${index}`} type="checkbox" defaultValue={uniName} defaultChecked={UNI} /></td>
              <td><input name={`mul[${j}]`} tabIndex="9" id={`key3${index}`} type="checkbox" defaultValue={mulName} defaultChecked={MUL}  /></td>
              <td><a href="#" onClick={clearKeys.bind(this, index)}>clear</a></td>
              <td><input name={`fulltext[${index}]`} tabIndex="11" id={`fulltext${index}`} type="checkbox" value="1" /></td>
              <td>
                  <select name="attr[]" tabIndex="12" id={`attr${index}`} style={{width:'70px'}} defaultValue={attr}>
                      <option value="">-</option>
                      <option>UNSIGNED</option>
                      <option>UNSIGNED ZEROFILL</option>
                  </select>
              </td>
              <td>{extra}</td>
          </tr>
        )
    });

    return (
      <TableHead>
          {trs}
      </TableHead>
    );
}


export function Tbl_add(props) {

    const removeRow = (tableId, param) => {
        removeRow(tableId, param)
    }

    useEffect(() => {
        forElementsEvent('change', '[name="ftype[]"]', function() {
            let curType = this.value;
            if (curType === 'SERIAL') {
                let autoinc = this.closest('tr').querySelector('td:nth-child(6) input');
                autoinc.checked = true
                autoinc.disabled = true
                let nulled = this.closest('tr').querySelector('td:nth-child(4) input');
                nulled.disabled = true
            }
            if (curType === 'ENUM' || curType === 'SET') {
                let value = this.closest('tr').querySelector('[name="length[]"]');
                value.value = "'','',''"
            }
        })
        qs('[name="table_name"]').focus()
    }, []);

    /**
     * Специальная функция для изменения параметров скопированного ряда. Сначала копируется ряд.
     * Далее меняются индексы у аттрибутов name, если требуется. Ид и прочие аттрибуты не трогаются пока.
     */
    const addDataRow = (id) => {
        let newTR = addRow(id);
        let inputs = newTR.getElementsByTagName('INPUT')
        for (let i = 0; i < inputs.length; i++) {
            let res = /([a-z]+)\[(\d+)\]/i.exec(inputs[i].name)
            if (res != null) {
                inputs[i].name = res[1] + '[' + (Number(res[2]) + 1) + ']';
            }
        }
    }

    const save = (e) => {
        e.preventDefault()
        msQuery('', e.target, () => {
            setTimeout(function() {
                if (showTableName) {
                    location.href = umaker({s: 'tbl_struct', table: document.querySelector('[name="table_name"]').value})
                } else {
                    location.href = umaker({s: 'tbl_struct', field: false})
                }
            }, 1000);
        })
    }

    const getAfterSql = () => {
        if (window.post.afterOption === 'start') {
            return 'FIRST'
        }
        if (window.post.afterOption === 'field') {
            return 'AFTER `'+window.post.afterField+'`'
        }
        return ''
    }

    let action = getAction()
    const field = GET('field')
    const showTableName = action === 'tableAddEnd' && !field
    const afterSql = getAfterSql()

    return (
      <form method="post" action="" className="tableFormEdit" name="addForm" onSubmit={save.bind(this)}>
          {showTableName &&
            <Fragment>
                <input tabIndex="1" type="text" name="table_name" size="40" defaultValue={props.tableName} /> имя таблицы <br />
            </Fragment>
          }
          {afterSql &&
            <input type="hidden" name="afterSql" value={afterSql} /> }
          <input type="hidden" name="action" value={action} />

          <img src={`${props.dirImage}nolines_plus.gif`} alt="" border="0" onClick={addDataRow.bind(this, 'tableFormEdit')} title="Добавить поле" style={{cursor: 'pointer'}} />
          <img src={`${props.dirImage}nolines_minus.gif`} alt="" border="0" onClick={removeRow.bind(this, 'tableFormEdit', 'end')}  title="Удалить поле" style={{cursor: 'pointer'}} /><br />

          <MSC_DrawFields {...props} />

          <input tabIndex="100" type="submit" value="Выполнить!" className="submit" />
      </form>
    );
}