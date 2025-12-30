import {ExportOptions, HtmlSelector} from "../components";
import React, {Fragment} from 'react';

function Form(props) {

    const createSet = () => {
        const m = prompt("Введите имя для установки", "Новая");
        if (m) {
            this.target.value = m;
        } else {
            return false;
        }
    }

    const selectSet = () => {
        window.location = "?db=<?=$msc->db?>&s=<?=$msc->page?>&set=" + this.target.options[this.target.selectedIndex].value
    }


    const trs = Object.values(props.data).map((item, i) => {

        let valueTable, where, valueMax, pKey
        let checkedStruct = false;
        let checkedData = true;

        // Определение видимости таблицы
        let configInfo = props.configSet[item.Name];
        if (!configInfo) {
            checkedData = false
        } else {
            // Определение необходимости экспорта данных и стр-ры
            if (configInfo.struct == 1) {
                checkedStruct = true
            }
            if (configInfo.data != 1) {
                checkedData = false
            }
            // Определение верхнего значение PK
            valueMax = configInfo.pk_top == 0 ? null : configInfo.pk_top;
            where = configInfo.where_sql;
        }

        // Определение ключевого поля и создание массива полей
        let fnames = []
        for (let field in item.Fields) {
            fnames.push(field)
            if (item.Fields[field].Key.indexOf("PRI") > -1) {
                pKey = field;
            }
        }
        // Создание ряда
        valueTable = item.Name
        if (!checkedData && !checkedStruct) {
            valueTable = <span style={{color: "#aaa"}}>{valueTable}</span>
        } else {
            valueTable = <b>{valueTable}</b>
        }

        return (
          <tr key={i}>
              <td><label htmlFor={`row${i}`}>{valueTable}</label></td>
              <td>
                  <input name={`struct[${i}]`} type="checkbox" value="1" id={`row${i}`} className="cb" defaultChecked={checkedStruct} />
                  <input name={`table[${i}]`} type="hidden" value={item.Name} />
              </td>
              <td><input name={`data[${i}]`} type="checkbox" value="1" id={`row2${i}`} className="cb" defaultChecked={checkedData} /></td>
              <td><HtmlSelector data={fnames} name={`from[${i}]`} value={pKey} /></td>
              <td><input name={`from[${i}]`} type="text" size="5" defaultValue="1" /> - <input name="to['.$i.']" type="text" size="5" defaultValue={valueMax} /></td>
              <td><input name={`where[${i}]`} type="text" size="40" defaultValue={where} /></td>
          </tr>
        )
    });

    //props.setsArray.unshift('Загрузить установку')
    return (
      <form method="post" action="" name="formExport">
          <input type="hidden" name="exportSpecial" value="1" />
          <ExportOptions fields={props.fields} dirImage={props.dirImage} structChecked={props.structChecked} />
          <p><input type="submit" value="Выполнить" /></p>
          <select onChange={selectSet} defaultValue={new URL(location.href).searchParams.get('set')}>
              {Object.values(props.setsArray).map((v) =>
                <option key={v.toString()}>{v}</option>
              )}
          </select>
          <input type="submit" name="save" value="Сохранить изменения" />
          <input type="submit" name="new" value="Создать новую установку" onClick={createSet} />
          <input type="submit" name="delete" value="Удалить установку" />

          <table className="contentTable">
              <thead>
              <tr>
                  <th>Таблица</th>
                  <th>Структ</th>
                  <th>Данные</th>
                  <th>Поле</th>
                  <th>Диапазон</th>
                  <th>WHERE</th>
              </tr></thead>
              <tbody>
              {trs}
              </tbody>
          </table>

      </form>
    );
}


function Results(props) {
    return <textarea name="export" rows="40" wrap="off" defaultValue={props.content}></textarea>
}

export function ExportSp(props) {
    return (
      <Fragment>
          {props.content ? <Results content={props.content} /> : <Form {...props} />}
      </Fragment>
    )
}