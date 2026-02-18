import React, {Fragment, useEffect} from 'react';
import {addRow, empty, forElementsEvent, GET, msQuery, qs, removeRow, umaker} from "../functions";
import {Messages} from "../functions";

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
              <th>Full</th>
              <th>Атрибуты</th>
              <th>После...</th>
          </tr></thead>
          <tbody>
          {props.children}
          </tbody>
      </table>
    );
}

function PrintFields(props) {

    const aiClick = (event) => {
        event.target.parentNode.previousElementSibling.firstChild.value = ''
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

    // Длина поля, получаем из типа
    const getLength = (type) => {
        if (type !== undefined) {
            let a = type.match(/\((.*)\)/)
            if (a) {
                return a[1]
            }
        }
        return ''
    }

    // Информация по ключам по текущему ряду поля
    const getKeys = (field, keys) => {
        let output = {}
        // Если открыто на редактирование - нужно подставить текущие значения
        if (typeof field == 'object') {
            let name = field.Field;
            if (keys[name]) {
                Object.keys(keys[name]).forEach(function(keyName) {
                    let key = keys[name][keyName];
                    output.PRI = key === "PRI" || POST.primaryKey === name
                    if (key === "UNI" || POST.uni && POST.uni[name]) {
                        output.UNI = true
                        output.uniName = keyName
                    }
                    if (key === "MUL" || POST.mul && POST.mul[name]) {
                        output.MUL = true
                        output.mulName = keyName
                    }
                });
            }
        }
        return output
    }

    let POST = window.post || {};
    let action = props.action

    // получение массива "предыдущих полей" полей
    let fields
    if (action === 'tableAddEnd' || action === 'fieldsAddEnd') {
        fields = []
        for (let i = 0; i < props.fieldsCount; i ++) {
            fields.push(i)
        }
    } else {
        fields = filterFields(props);
    }

    const trs = fields.map((field, index) => {

        let keys = getKeys(field, props.keys)
        let j = field.Field === '' ? index : field.Field;

        return (
          <tr key={index}>
              <td>
                  <input name="name[]" type="text" defaultValue={field.Field} size="15" />
                  <input type="hidden" name="oldname[]" defaultValue={field.Field} />
              </td>
              <td><TypeSelect type={field.Type} action={action} /> </td>
              <td><input name="length[]" type="text" defaultValue={getLength(field.Type)} size="30" /></td>
              <td><input name={`isNull[${index}]`} type="checkbox" value="1" defaultChecked={field.Null === "YES"} /></td>
              <td><input name="default[]" type="text" size="10" defaultValue={field.Default} /></td>
              <td><input name={`auto[${index}]`} onClick={aiClick} type="checkbox" value="1" defaultChecked={!empty(field.Extra)}  /></td>
              <td><input name="primaryKey" type="radio" defaultValue={j} defaultChecked={keys.PRI} /></td>
              <td><input name={`uni[${j}]`} type="checkbox" defaultValue={keys.uniName} defaultChecked={keys.UNI} /></td>
              <td><input name={`mul[${j}]`} type="checkbox" defaultValue={keys.mulName} defaultChecked={keys.MUL}  /></td>
              <td><input name={`fulltext[${index}]`} type="checkbox" value="1" /></td>
              <td><UnsignedSelect type={field.Type} /></td>
              <td><AfterFieldSelect field={field} fields={props.fields} post={POST} index={index} action={action} /></td>
          </tr>
        )
    });

    return (
      <TableHead>
          {trs}
      </TableHead>
    );
}

export function AfterFieldSelect(props) {
    if (window.driver === 'pgsql') {
        return null
    }
    let fieldsAfter = ['FIRST']
    let previousFields = {}
    let prev = '';
    props.fields.forEach(item => {
        previousFields[item.Field] = prev
        prev = item.Field
        fieldsAfter.push(item.Field)
    })
    let POST = props.post
    let index = props.index
    let field = props.field
    if (typeof field == 'object') {
        // редактирование значений
        return (
          <Fragment>
              <select name="after[]" defaultValue={previousFields[field.Field]}>
                  {fieldsAfter.map((v) =>
                    <option key={v}>{v}</option>
                  )}
              </select>
              <input type="hidden" name="afterold[]" defaultValue={previousFields[field.Field] || 'FIRST'}/>
          </Fragment>
        );
    } else if (POST.afterOption) {
        // Добавление новых полей
        let checked = ''
        if (POST.afterOption === 'end') {
            checked = fieldsAfter[fieldsAfter.length - 1]
        } else if (POST.afterOption === 'field') {
            checked = POST.afterField
        }
        return (
          <Fragment>
              <select name={`after[${index}]`} defaultValue={checked}>
                  {fieldsAfter.map((v) =>
                    <option key={v}>{v}</option>
                  )}
              </select>
              <input type="hidden" name={`afterold[${index}]`} defaultValue={checked}/>
          </Fragment>
        );
    }
}

export function TypeSelect(props) {
    // создание селектора типов данных
    let columnTypesByGroups
    if (window.driver === 'pgsql') {
        // https://metanit.com/sql/postgresql/2.3.php
        columnTypesByGroups = {
            'String': ['CHARACTER','CHARACTER VARYING','TEXT',],
            'AutoIncrement': ['SERIAL','SMALLSERIAL','BIGSERIAL',],
            'Numeric': ['SMALLINT','INTEGER','BIGINT',],
            'Float': ['NUMERIC','DECIMAL','REAL','DOUBLE PRECISION',],
            'DateTime': ['TIMESTAMP','DATE','TIME','INTERVAL',], // 'TIMESTAMP WITH TIME ZONE', 'TIME WITH TIME ZONE'
            'Other': ['BOOLEAN','JSON','JSONB','UUID','XML','MONEY','BYTEA',],
            'Ip': ['CIDR','INET','MACADDR','MACADDR8'],
            //'SPATIAL': ['POINT', 'LINE', 'LSEG', 'BOX', 'PATH', 'POLYGON', 'CIRCLE', ],
        };
    } else {
        columnTypesByGroups = {
            'String': ['VARCHAR', 'TEXT','CHAR','TINYTEXT','MEDIUMTEXT','LONGTEXT',],
            'Numeric': ['INT', 'SMALLINT', 'TINYINT', 'MEDIUMINT', 'BIGINT',],
            'Float': ['FLOAT', 'DOUBLE', 'DECIMAL',],
            'DateTime': ['DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR',],
            'Blob': ['TINYBLOB',  'BLOB', 'MEDIUMBLOB', 'LONGBLOB', ],
            //'Spatial': [],
            'Other': ['ENUM', 'SET', 'JSON', 'BOOLEAN', 'SERIAL'],
        };
    }
    let columnTypes = [];
    for (let key in columnTypesByGroups) {
        columnTypes = [...columnTypes, ...columnTypesByGroups[key]]
    }
    let value = ''
    if (props.type) {
        value = props.type.replace(/\((.*)\).*/i, '').toUpperCase()
        value = value.replace(/\s*(UNSIGNED)( ZEROFILL)?/, '')
        if (props.action === 'fieldsEditEnd') {
            if (!columnTypes.includes(value)) {
                console.error(`Тип ${value} не найден в списке`)
                Messages.show({'messages': `Тип ${value} не найден в списке`})
            }
        }
    }
    return (
      <select name="ftype[]" defaultValue={value}>
          {Object.keys(columnTypesByGroups).map((label) =>
            <optgroup label={label}>
                {columnTypesByGroups[label].map((type) =>
                  <option key={type}>{type}</option>
                )}
            </optgroup>
          )}
      </select>
    );
}

export function UnsignedSelect(props) {
    if (window.driver === 'pgsql') {
        return null
    }
    let value = ''
    if (props.type) {
        const isUnsignedZero = props.type.match(/unsigned zerofill/i) !== null;
        const isUnsigned = props.type.match(/unsigned/i) !== null;
        value = isUnsigned ? 'UNSIGNED' : (isUnsignedZero ? 'UNSIGNED ZEROFILL' : '');
    }
    return (
      <select name="attr[]" style={{width: '70px'}} defaultValue={value}>
          <option value="">-</option>
          <option>UNSIGNED</option>
          <option>UNSIGNED ZEROFILL</option>
      </select>
    );
}


export function Tbl_add(props) {

    useEffect(() => {
        forElementsEvent('change', '[name="ftype[]"]', function () {
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
                value.value = "'a','b','c'"
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
        msQuery(action, e.target, () => {
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
                <input type="text" name="table_name" size="40" defaultValue={props.tableName} /> имя таблицы <br />
            </Fragment>
          }
          {afterSql &&
            <input type="hidden" name="afterSql" value={afterSql} /> }
          <input type="hidden" name="action" value={action} />

          <img src={`${props.dirImage}nolines_plus.gif`} alt="" border="0" onClick={addDataRow.bind(this, 'tableFormEdit')} title="Добавить поле" style={{cursor: 'pointer'}} />
          <img src={`${props.dirImage}nolines_minus.gif`} alt="" border="0" onClick={removeRow.bind(this, 'tableFormEdit', 'end')}  title="Удалить поле" style={{cursor: 'pointer'}} /><br />

          <PrintFields {...props} action={action} />

          <input type="submit" value="Выполнить!" className="submit" />
      </form>
    );
}