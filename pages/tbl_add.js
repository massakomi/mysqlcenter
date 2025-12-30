import React, {Fragment, useEffect} from 'react';
import {addRow, forElementsEvent, msQuery, qs, umaker} from "../js/MysqlCenter";

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


    let POST = props.post || {};

    let array = props.array;

    // получение массива из post
    if (array.length === 0) {
        alert("TODO")
        Object.keys(POST.name).forEach(function(key) {
            //let name = POST.name[key]
        })
        /*
                $array = array();
                foreach ($_POST['name'] as $key => $name) {
                    if (empty($name)) {
                        continue;
                    }
                    $object = new A;
                    $object->Field   = $name;
                    $object->Type    = strtolower($_POST['ftype'][$key]);
                    if ($_POST['length'][$key] > 0) {
                        $object->Type .= '('. $_POST['length'][$key].')';
                    }
                    if ($_POST['attr'][$key] == 'UNSIGNED ZEROFILL') {
                        $object->Type .= ' UNSIGNED ZEROFILL';
                    } else if ($_POST['attr'][$key] == 'UNSIGNED') {
                        $object->Type .= ' UNSIGNED';
                    }
                    $object->Null    = isset($_POST['isNull'][$key]) ? 'YES' : '';
                    if (isset($_POST['uni'][$key])) {
                        $object->Key = 'UNI';
                    }
                    if (isset($_POST['mul'][$key])) {
                        $object->Key = 'MUL';
                    }
                    $object->Key = $_POST['primaryKey'] == $name ? 'PRI' : '';
                    $object->Default = $_POST['default'][$key];
                    $object->Extra = '';
                    if (isset($_POST['auto'][$key])) {
                        $object->Extra   = 'AUTO_INCREMENT';
                    }
                    $array []= $object;
                }
        * */
    }

    let keys = props.keys;

    // получение массива "предыдущих полей" полей
    let fields = ['FIRST']
    let previousFields = {}
    let prev = '';
    props.fields.forEach(function(field) {
        previousFields[field] = prev
        prev = field
        fields.push(field)
    })

    // создание селектора типов данных
    let columnTypes = [
        'VARCHAR', 'TINYINT', 'TEXT', 'DATE', 'JSON',
        'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT',
        'FLOAT', 'DOUBLE', 'DECIMAL',
        'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR',
        'CHAR', 'TINYBLOB', 'TINYTEXT', 'BLOB', 'MEDIUMBLOB', 'MEDIUMTEXT', 'LONGBLOB', 'LONGTEXT',
        'ENUM', 'SET', 'BOOLEAN', 'SERIAL'
    ];

    const trs = array.map((v, k) => {

        let NAME='', TYPE='', LENGTH='', DEFAULT='', ISNULL='', AUT='', extra='';
        let isUnsignedZero=false, isUnsigned=false;
        let PRI='', UNI='', MUL='', uniName='', mulName='';

        if (typeof v == 'object') {
            let a = v.Type.match(/\((.*)\)/)
            if (a) {
                LENGTH = a[1]
            }
            isUnsignedZero = v.Type.match(/unsigned zerofill/i) !== null;
            isUnsigned = v.Type.match(/unsigned/i) !== null;
            NAME = v.Field;
            TYPE = v.Type.replace(/\((.*)\).*/i, '').toUpperCase();
            DEFAULT = v.Default;
            ISNULL = v.Null === true || v.Null === "YES";
            AUT = v.Extra !== ""
            extra = (
              <Fragment>
                  <select name="after[]" defaultValue={previousFields[v.Field]}>
                      {fields.map((v) =>
                        <option key={v}>{v}</option>
                      )}
                  </select>
                  <input type="hidden" name="afterold[]" defaultValue={previousFields[v.Field] || 'FIRST'} />
              </Fragment>
            )
            console.log(NAME)
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
                checked = fields[fields.length - 1]
            } else if (POST.afterOption === 'field') {
                checked = POST.afterField
            }
            extra = (
              <Fragment>
                  <select name={`after[${k}]`} defaultValue={checked}>
                      {fields.map((v) =>
                        <option key={v}>{v}</option>
                      )}
                  </select>
                  <input type="hidden" name={`afterold[${k}]`} defaultValue={checked} />
              </Fragment>
            )
        }

        let j = NAME === '' ? k : NAME;

        let attr = isUnsigned ? 'UNSIGNED' : (isUnsignedZero ? 'UNSIGNED ZEROFILL' : '');

        return (
          <tr key={k} id={`tableFormEditTr${k}`}>
              <td>
                  <input name="name[]" tabIndex="1" id={`name${k}`} type="text" defaultValue={NAME} size="15" />
                  <input type="hidden" name="oldname[]" defaultValue={NAME} />
              </td>
              <td>
                  <select name="ftype[]" tabIndex="2" id={`typeSelectorId${k}`} defaultValue={TYPE}>
                      {columnTypes.map((v) =>
                        <option key={v}>{v}</option>
                      )}
                  </select>
              </td>
              <td><input name="length[]" tabIndex="3" id={`length${k}`} type="text" defaultValue={LENGTH} size="30" /></td>
              <td><input name={`isNull[${k}]`} tabIndex="4" id={`isNull${k}`} type="checkbox" value="1" defaultChecked={ISNULL} /></td>
              <td><input name="default[]" tabIndex="5" id={`default${k}`} type="text" size="10" defaultValue={DEFAULT} /></td>
              <td><input name={`auto[${k}]`} tabIndex="6" id={`auto${k}`} onClick={aiClick.bind(this, k)} type="checkbox" value="1" defaultChecked={AUT}  /></td>
              <td><input name="primaryKey" tabIndex="7" id={`key1${k}`} type="radio" defaultValue={j} defaultChecked={PRI} /></td>
              <td><input name={`uni[${j}]`} tabIndex="8" id={`key2${k}`} type="checkbox" defaultValue={uniName} defaultChecked={UNI} /></td>
              <td><input name={`mul[${j}]`} tabIndex="9" id={`key3${k}`} type="checkbox" defaultValue={mulName} defaultChecked={MUL}  /></td>
              <td><a href="#" onClick={clearKeys.bind(this, k)}>clear</a></td>
              <td><input name={`fulltext[${k}]`} tabIndex="11" id={`fulltext${k}`} type="checkbox" value="1" /></td>
              <td>
                  <select name="attr[]" tabIndex="12" id={`attr${k}`} style={{width:'70px'}} defaultValue={attr}>
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
                if (props.showTableName) {
                    location.href = umaker({s: 'tbl_struct', table: document.querySelector('[name="table_name"]').value})
                } else {
                    location.href = umaker({s: 'tbl_struct', field: false})
                }
            }, 1000);
        })
    }

    return (
      <form method="post" action="" className="tableFormEdit" name="addForm" onSubmit={save.bind(this)}>
          {props.showTableName &&
            <Fragment>
                <input tabIndex="1" type="text" name="table_name" size="40" defaultValue={props.tableName} /> имя таблицы <br />
            </Fragment>
          }
          {props.afterSql &&
            <input type="hidden" name="afterSql" value={props.afterSql} /> }
          <input type="hidden" name="action" value={props.action} />

          <img src={`${props.dirImage}nolines_plus.gif`} alt="" border="0" onClick={addDataRow.bind(this, 'tableFormEdit')} title="Добавить поле" style={{cursor: 'pointer'}} />
          <img src={`${props.dirImage}nolines_minus.gif`} alt="" border="0" onClick={removeRow.bind(this, 'tableFormEdit', 'end')}  title="Удалить поле" style={{cursor: 'pointer'}} /><br />

          <MSC_DrawFields {...props} />

          <input tabIndex="100" type="submit" value="Выполнить!" className="submit" />
      </form>
    );
}