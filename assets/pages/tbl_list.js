import React, {useState, Fragment} from 'react';
import {Table} from "../components";
import {checkboxAction, date2rusString, formatSize, msImageAction, msQuery} from "../functions";

function Selector(props) {

    let options = []

    if (props.data) {
        options = props.data.map((value, i) =>
          <option key={i} value={i}>{value}</option>
        );
    } else {
        for (let i = props.from; i <= props.to; i ++) {
            options.push(
              <option key={i}>{i}</option>
            )
        }
    }

    return (
      <select name={props.name} defaultValue={props.value}>
          {options}
      </select>
    );
}

function DateSelector() {

    Date.prototype.daysInMonth = function() {
        return 32 - new Date(this.getFullYear(), this.getMonth(), 32).getDate();
    };

    let date = new Date()
    let months = ['января', 'февраля', 'марта', 'апреля', 'май', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря']

    return (
      <Fragment>
          <Selector name="ds_day" from="1" to={date.daysInMonth()} value={date.getDate()} />
          <Selector name="ds_month" data={months} value={date.getMonth()} />
          <Selector name="ds_year" from="2000" to={date.getFullYear()} value={date.getFullYear()} />
          <Selector name="ds_hour" from="0" to="23" /> :
          <Selector name="ds_minut" from="0" to="59" /> :
          <Selector name="ds_second" from="0" to="59" />
      </Fragment>
    );
}


function TableList(props) {
    
    const image = src => <img src={props.dirImage + src} alt=""/>;

    const tableDelete = async (table, e) => {
        e.preventDefault()
        let tr = e.target.closest('tr')
        let query = `db=${props.db}&table=${table}`;
        await msQuery('tableDelete', query, () => {
            tr.remove()
        })
    };

    const renameTable = (tableOld, e) => {
        let label = e.target
        let newName = prompt('Новое имя', tableOld)
        if (newName) {
            let q = `db=${props.db}&s=tbl_list&table=${tableOld}&newName=${newName}`;
            msQuery('tableRename', q, () => {
                label.innerHTML = newName
            });
        }
    };

    const printSize = (size, tablesCount) => {
        // меньше 1мб не нужно выводить
        if (tablesCount > 100) {
            if (size < 1024*1024) {
                return ''
            }
        } else {
            if (size < 1024) {
                return ''
            }
        }
        let color = 'red'
        // до 10мб слабже
        if (size < 1024*1024*20) {
            color = '#aaa'
        } else if (size < 1024*1024*100) {
            color = 'black'
        }
        let formattedSize = formatSize(size)
        return <span title={size} style={{'color': color}}>{formattedSize}</span>
    };

    const getCollation = (table) => {
        let collation = table.Collation
        let underline = collation.indexOf("_");
        if (underline > 0) {
            collation = collation.substring(0, underline)
        }
        return collation
    }

    const renderRow = (table, key, tablesCount) => {
        // Увеличение счётчика видимых таблиц
        let sumTable = key + 1
        // Форматирование даты
        let updateTime = null;

        if (table.Update_time) {
            const dateUt = new Date(table.Update_time);
            updateTime = date2rusString(dateUt)
            if (updateTime.match(/(дня|ера)/i)) {
                updateTime = <b>{updateTime}</b>
            }
        }
        // Форматирование названия таблицы
        let valueName = table.Name
        if (table.Rows === '0') {
            valueName = <span style={{color: '#aaa'}}> {valueName}</span>
        }
        // Определение размера таблицы
        const size = parseInt(table.Data_length) + parseInt(table.Index_length);
        sumSize += parseInt(size);
        sumRows += parseInt(table.Rows);
        // Сборка значения рядов
        const msquery = `db=${props.db}&table=${table.Name}`;
        const idRow = "row" + sumTable;
        const idChbx = 'table_' + table.Name
        const engine = table.Engine === 'MyISAM' ? <span style={{color: '#ccc'}}>MyISAM</span> : table.Engine;

        return (
          <tr key={table.Name} id={idRow}>
              <td><input name="table[]" type="checkbox" value={table.Name} id={idChbx} className="cb" /></td>
              <td className="tbl"><label htmlFor={idChbx} onDoubleClick={renameTable.bind(this, table.Name)}>{valueName}</label></td>
              <td><a href={`/?db=${props.db}&table=${table.Name}&s=tbl_data`} title="Обзор таблицы">{image("actions.gif")}</a></td>
              <td><a href={`/?db=${props.db}&table=${table.Name}&s=tbl_struct`} title="Структура таблицы">{image("generate.png")}</a></td>
              <td>
                  <a href="#" onClick={msQuery.bind(this, 'tableTruncate', msquery)} title="Очистить таблицу">{image("delete.gif")}</a>
              </td>
              <td>
                  <a href="#" onClick={tableDelete.bind(this, table.Name)} title="Удалить таблицу">{image("close.png")}</a>
              </td>
              <td className="rig">{table.Rows}</td>
              <td className="rig">{printSize(size, tablesCount)}</td>
              <td>{updateTime}</td>
              <td className="num">{table.Auto_increment}</td>
              <td><span>{engine}</span></td>
              <td className="rig"><span title={table.Collation} style={{color: '#aaa'}}>{getCollation(table)}</span></td>
          </tr>
        )
    };
   

    const tables = Object.values(props.tables)
    let sumSize = 0;
    let sumRows = 0;
    const trs = tables.map((table, key) => renderRow(table, key, tables.length))

    return (
      <table className="contentTable interlaced">
          <thead>
          <tr>
              <th></th>
              <th>Таблица</th>
              <th></th>
              <th></th>
              <th></th>
              <th></th>
              <th>Рядов</th>
              <th>Размер</th>
              <th>Дата обновления</th>
              <th>Ai</th>
              <th>Engine</th>
              <th>Cp</th>
          </tr></thead>
          <tbody>
          {trs}
          <tr>
              <td></td>
              <td className="tbl">{tables.length} таблиц</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td className="rig">{Number(sumRows).toFixed(0)}</td>
              <td className="rig">{printSize(sumSize)}</td>
              <td></td>
              <td className="num"></td>
              <td></td>
              <td className="rig"></td>
          </tr></tbody>
      </table>
    );
}



export function Tbl_list(props) {

    const [tables, setTables] = useState(props.tables);

    const imageAction = (opt, url) => {
        if (opt === 'auto') {
            opt = this.target.options[this.target.selectedIndex].value
        }
        msImageAction('formTableList', opt, url)
    }

    const chbxAction = (opt, e) => {
        e.preventDefault()
        checkboxAction('formTableList', opt, 'table[]')
    }

    const image = src => props.dirImage + src;

    const filterByDate = () => {
        let year = document.querySelector('[name="ds_year"]').value
        let month = document.querySelector('[name="ds_month"]').value
        let day = document.querySelector('[name="ds_day"]').value
        let date = new Date(year, month, day)

        let tables = Object.values(props.tables).filter((table) => {
            let now = new Date(table.Update_time);
            return now > date;
        })
        setTables(tables)
    };

  
    if (props.full) {
        return <Table data={props.tables} />
    }

    return (
      <div>
          <form action={"?db="+props.db} method="post" name="formTableList" id="formTableList">

              <input type="hidden" name="tableMulty" value="1" />
              <input type="hidden" name="action" value="" />

              <TableList tables={tables} dirImage={props.dirImage} db={props.db} />

              <div className="chbxAction">
                  <img src={image("arrow_ltr.png")} alt=""  />
                  <a href="#" onClick={chbxAction.bind(this, 'check')} id="chooseAll">выбрать все</a>  &nbsp;
                  <a href="#" onClick={chbxAction.bind(this, 'uncheck')}>очистить</a>
              </div>

              <div className="imageAction">
                  <u>Выбранные</u>
                  <img src={image("close.png")} alt="" onClick={imageAction.bind(this, 'delete_all', '')} />
                  <img src={image("delete.gif")} alt="" onClick={imageAction.bind(this, 'truncate_all', '')} />
                  <img src={image("copy.gif")} alt="" onClick={imageAction.bind(this, 'copy_all', '')} />
                  <img src={image("b_tblexport.png")} alt="" onClick={imageAction.bind(this, 'export_all', `?db=${props.db}&s=export`)} />

                  <select name="act" onChange={imageAction.bind(this, 'auto', '')} >
                      <option></option>
                      <option value="check">проверить</option>
                      <option value="analyze">анализ</option>
                      <option value="optimize">оптимизировать</option>
                      <option value="repair">починить</option>
                      <option value="flush">сбросить кэш</option>
                  </select>

                  <input type="hidden" name="copy_struct" value="1" />
                  <input type="hidden" name="copy_data" value="1" />
              </div>
          </form>
          <div className="links-block">
              <a href="?s=tbl_list&action=full" title="Отобразить простую таблицу с полными данными всех таблиц, полученными с помощью запроса SHOW TABLE STATUS">Полная таблица</a>
              <a href="?s=tbl_list&action=structure">Исследование структуры таблиц</a>
          </div>
          {props.showtableupdated > 0 &&
            <form className="showtableupdated">
                Показать таблицы обновлённые с <DateSelector />
                <input type="button" value="Показать!" className="ml-10" onClick={filterByDate.bind(this)} />
            </form>
          }
      </div>
    );
}