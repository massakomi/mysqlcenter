import React from 'react';
import {checkboxAction, msImageAction, msQuery} from "../functions";

function TableFull(props) {

    const dbDelete = db => {
        msQuery('dbDelete', `db=${db}&id=db${db}`)
    };


    let nz = Intl.NumberFormat(undefined, {'maximumFractionDigits': 0});
    let nf = Intl.NumberFormat(undefined, {'minimumFractionDigits': 1, 'maximumFractionDigits': 1});
    let trs = [], countTotalTables = 0, countTotalSize = 0, countTotalRows = 0;
    for (let i = 0; i < props.databases.length; i++) {
        let db = props.databases[i]['name'];
        let href = `/?db=${db}&s=tbl_list`
        let idRow = "db"+db;

        let extra = props.databases[i]['extra'];
        let countTables = 0, countSize = 0, countRows = 0, updateTime = 0;
        for (let status of extra) {
            countTables ++;
            if (status.Update_time) {
                let ts = Date.parse(status.Update_time) / 1000;
                if (ts > updateTime) {
                    updateTime = ts
                }
            }
            let rows = parseInt(status.Rows)
            if (!isNaN(rows)) {
                countRows += rows
            }
            let size = ((parseInt(status.Data_length) + parseInt(status.Index_length)) / 1024)
            if (!isNaN(size)) {
                countSize += size
            }
        }

        countTotalTables += countTables
        countTotalSize += countSize
        countTotalRows += countRows

        if (updateTime) {
            updateTime = new Date(updateTime * 1000)
            updateTime = updateTime.toLocaleString()
        }

        trs.push(
          <tr key={i}>
              <td><input name="databases[]" type="checkbox" value={db} className="cb" /></td>
              <td><a href={href} title="Структура БД" id={idRow}>{db}</a></td>
              <td><a href="#" onClick={dbDelete.bind(this, db)} title={'Удалить '+db}><img src={"/" + props.folder + "close.png"} alt="" border="0" /></a></td>
              <td>{countTables}</td>
              <td>{updateTime}</td>
              <td>{nz.format(countRows)}</td>
              <td>{nf.format(countSize)}</td>
          </tr>
        )
    }

    return (
      <table className="contentTable" id="structureTableId">
          <thead>
          <tr>
              <th></th>
              <th>Название</th>
              <th></th>
              <th>Таблиц</th>
              <th>Обновлено</th>
              <th>Рядов</th>
              <th>Размер</th>
          </tr>
          </thead>
          <tbody>
          {trs}
          </tbody>
          <tfoot>
          <tr>
              <td></td>
              <td></td>
              <td></td>
              <td>{countTotalTables}</td>
              <td></td>
              <td>{nz.format(countTotalRows)}</td>
              <td>{nf.format(countTotalSize)}</td>
          </tr>
          </tfoot>
      </table>
    );
}

function Table(props) {

    const dbDelete = (db, event) => {
        msQuery('dbDelete', `dbDelete=${db}`, () => {
            event.target.closest('tr').remove()
        })
    };

    const dbHide = (db, action) => {
        msQuery('dbHide', `db=${db}&id=db${db}&action=${action}`)
    };

    let mscExists = props.databases.includes('mysqlcenter')
    let trs = []
    for (let i = 0; i < props.databases.length; i++) {
        let db = props.databases[i];
        let styles = {}
        let action = '';
        if (mscExists && props.hiddens && props.hiddens.includes(db)) {
            styles = {color: '#ccc'}
            action = 'show'
        }
        let href = `/?db=${db}&s=tbl_list`
        let idRow = "db"+db;
        // Добавляем в комментарий имя БД, чтобы в автотестах определить, куда кликать при проверке удаления
        trs.push(
          <tr key={i}>
              <td><input name="databases[]" type="checkbox" value={db} className="cb" /></td>
              <td><a href={href} title="Структура БД" id={idRow} style={styles}>{db}</a></td>
              <td>
                  <a href="#" onClick={dbDelete.bind(this, db)} title={'Удалить '+db}><img src={"/" + props.folder + "close.png"} alt="" border="0" /></a> &nbsp;
                  <a href={'/?db=' + db + '&s=actions'} title="Изменить"><img src={"/" + props.folder + "edit.gif"} alt="" border="0" /></a> &nbsp;
                  {mscExists ?
                    <a href="#" onClick={dbHide.bind(this, db, action)} title={'Спрятать/показать ' + db}><img src={"/" + props.folder + "open-folder.png"} alt="" border="0" width="16" /></a> : null}
              </td>
          </tr>
        )
    }

    return (
      <table className="contentTable" id="structureTableId">
          <thead>
          <tr>
              <th></th>
              <th>Название</th>
              <th></th>
          </tr>
          </thead>
          <tbody>
          {trs}
          </tbody>
      </table>
    );
}

function ColumnLeft(props) {

    const imageAction = (param, actionReplace) => {
        if (typeof actionReplace == 'string') {
            actionReplace = props.url + '?s=' + actionReplace
        } else {
            actionReplace = ''
        }
        msImageAction('formDatabases', param, actionReplace)
    }

    const chbxAction = opt => {
        checkboxAction('formDatabases', opt, 'databases[]')
    };


    return (
      <div>
          <form action="?s=db_list" method="post" name="formDatabases" id="formDatabases">
              <input type="hidden" name="dbMulty" value="1" />
              <input type="hidden" name="action" value="" />
              {!props.showFullInfo ?
                <Table folder={props.folder} databases={props.databases} hiddens={props.hiddens} /> :
                <TableFull folder={props.folder} databases={props.databases} hiddens={props.hiddens} />}
          </form>

          <div className="chbxAction">
              <img src={"/" + props.folder + "arrow_ltr.png"} alt="" border="0" align="absmiddle" />
              <a href="#" onClick={chbxAction.bind(this, 'check')}>выбрать все</a>  &nbsp;
              <a href="#" onClick={chbxAction.bind(this, 'uncheck')}>очистить</a>
          </div>

          <div className="imageAction">
              <u>Выбранные</u>
              <input type="image" src={"/" + props.folder + "close.png"} onClick={imageAction.bind(this, 'dbDelete')} title="Удалить базы данных" alt="" />
              <input type="image" src={"/" + props.folder + "copy.gif"} onClick={imageAction.bind(this, 'dbCopy')} title="Скопировать базы данных по шаблону {db_name}_copy" alt="" />
              <input type="image" src={"/" + props.folder + "b_tblexport.png"} onClick={imageAction.bind(this, 'exportDatabases', 'export')} title="Перейти к экспорту баз данных" alt="" />
              <input type="image" src={"/" + props.folder + "fixed.gif"} onClick={imageAction.bind(this, 'db_compare', 'db_compare')} title="Сравнить выбранные базы данных" alt="" />
          </div>
      </div>
    );
}




function ColumnRight(props) {

    let tableLink;
    if (!props.showFullInfo) {
        tableLink = <a href={`?s=db_list&db=${props.dbname}&mode=full`} title="Сканирует все таблицы всех баз данных и выводит количество таблиц, размер, дату обновления и количество рядов">Показать полную таблицу</a>
    } else {
        tableLink = <a href={`?s=db_list&db=${props.dbname}`}>Показать краткую таблицу</a>
    }

    return (
      <div>
          <DbCreateForm />

          <div className="mt-10">
              {tableLink} <br/>
              <a href={`?s=actions&info=1`}>Информация сервера</a>
          </div>

          <div className="mt-10">Хост: {props.dbHost}</div>
          <div>Версия сервера: {props.mysqlVersion}</div>
          <div>Версия PHP: {props.phpversion}</div>
          <div>БД: {props.dbname}<br /></div>
      </div>
    );
}

function DbCreateForm() {
    return  (
      <fieldset className="msGeneralForm">
          <legend>Создание базы данных</legend>
          <form action="?s=tbl_list&action=dbCreate" method="post">
              <input name="dbName" type="text" defaultValue="" />
              <button type="submit">Создать!</button>
          </form>
      </fieldset>
    )
}

export function Db_list(props) {
    return  (
      <div className="flex">
          <ColumnLeft folder={props.folder} url={props.url} showFullInfo={props.showFullInfo} databases={props.databases} hiddens={props.hiddens} />

          <ColumnRight appName={props.appName} appVersion={props.appVersion} dbHost={props.dbHost} showFullInfo={props.showFullInfo} dbname={props.dbname}
                       phpversion={props.phpversion} mysqlVersion={props.mysqlVersion} />
      </div>
    )
}