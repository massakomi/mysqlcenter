import {Fragment, useState} from "react";
import React from 'react';
import {CharsetSelector, HtmlSelector} from "../components";
import {empty, msQuery} from "../functions";
import {Actionsdb} from "./actionsdb";
import {Table} from "../components/Table";

export function Actions(props) {

    return (
      <Fragment>
          {props.table ? <ActionsTable {...props} /> : <Actionsdb {...props} />}
      </Fragment>
    );
}
function PgIdentityInfo(props) {
  if (window.driver !== 'pgsql' || empty(props.identityInfo)) {
    return null
  }
  return (
    <Fragment>
      <h3>PostgresSQL identity info</h3>
      <Table data={props.identityInfo} />
    </Fragment>
  );
}

function ActionFieldset(props) {
  return (
    <fieldset className="msGeneralForm">
      <legend>{props.legend}</legend>
      <form>{props.children}</form>
    </fieldset>
  );
}


function ActionsTable(props) {
    
    const [comment, setComment] = useState(props.comment);
    const [renameName, setRenameName] = useState(props.table);

    let onChangeComment = (e) => {
        setComment(e.target.value)
    }

    let onChangeRenameName = (e) => {
        setRenameName(e.target.value)
    }

    let tableAction = (mode, e) => {
        e.preventDefault()
        const form = e.target.closest('form')
        let btn
        if (form !== null) {
            btn = form.querySelector('[type="button"], [type="submit"]')
            btn.disabled = true
            setTimeout(function() {
                btn.disabled = false
            }, 5000);
        }
        msQuery(mode, form, (data) => {
            setTimeout(function() {
                if (data.status === true && mode === 'tableRename') {
                    window.location = '?s=tbl_data&table=' + form.querySelector('[name="newName"]').value
                }
            }, 1000);
        })
    }


    return (
      <Fragment>

          <ActionFieldset legend="Переименовать таблицу в:">
              <input name="newName" type="text" onChange={onChangeRenameName} required value={renameName}/>
              <input type="submit" value="Выполнить!" onClick={tableAction.bind(this, "tableRename")}
                     disabled={!renameName} className="submit"/>
          </ActionFieldset>

          <ActionFieldset legend="Переместить таблицы в (база данных.таблица):">
              <HtmlSelector data={props.dbs} name="newDB" auto="false" value={props.db}/>
              .
              <input name="newName" required type="text" defaultValue={props.table}/>
              <input type="submit" onClick={tableAction.bind(this, "tableMove")} value="Выполнить!"
                     className="submit"/>
          </ActionFieldset>

          <ActionFieldset legend="Скопировать таблицу в (база данных.таблица):">
              <HtmlSelector data={props.dbs} value={props.db} name="newDB"/>
              .
              <input name="newName" type="text" required defaultValue={props.table}/>
              <div className="mt-10">
                  <input type="submit" onClick={tableAction.bind(this, "tableCopyTo")} value="Выполнить!"/>
                  <input type="checkbox" name="tableCopyNoData" value="1"/> только структуру
              </div>
          </ActionFieldset>

          {!empty(props.charsets) ? <ActionFieldset legend="Изменить кодировку таблицы">
              <CharsetSelector charsets={props.charsets} value={props.charset}/>
              <input type="button" onClick={tableAction.bind(this, "tableCharset")} value="Выполнить!"
                     className="ml-10"/>
          </ActionFieldset> : null}

          <ActionFieldset legend="Комментарий к таблице">
              <input name="comment" type="text" size="60" onChange={onChangeComment} defaultValue={comment}/>
              <input type="submit" onClick={tableAction.bind(this, "tableComment")} value="Выполнить!"
                     disabled={!comment} className="submit"/>
          </ActionFieldset>
          {window.driver === 'mysql' ? (
            <ActionFieldset legend="Изменить порядок">
                <HtmlSelector data={props.fields} name="field"/>
                <select name="order" className="ml-10">
                    <option value="">По возрастанию</option>
                    <option value="DESC">По убыванию</option>
                </select>
                <input type="submit" onClick={tableAction.bind(this, "tableOrder")} value="Выполнить!"
                       className="ml-10"/>
            </ActionFieldset>
          ) : null}
          <ActionFieldset legend="Опции таблицы">
              <input name="auto_increment" type="text" size="3" defaultValue={props.ai}/> auto_increment
              <input type="submit" onClick={tableAction.bind(this, "tableOptions")} value="Выполнить!"
                     className="submit"/>
              <PgIdentityInfo identityInfo={props.identityInfo} />
          </ActionFieldset>

          {window.driver === 'mysql' ? <div className="globalMenu mb-20">
              <a onClick={tableAction.bind(this, "tableCheck")} href="#">Проверить таблицу</a>
              <a onClick={tableAction.bind(this, "tableAnalize")} href="#">Анализ таблицы</a>
              <a onClick={tableAction.bind(this, "tableRepair")} href="#">Починить таблицу</a>
              <a onClick={tableAction.bind(this, "tableOptimize")} href="#">Оптимизировать таблицу</a>
              <a onClick={tableAction.bind(this, "tableFlush")} href="#">Сбросить кэш таблицы (FLUSH)</a>
          </div> : null}
      </Fragment>
    );
}
