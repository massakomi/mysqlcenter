import {Fragment, useState} from "react";
import React from 'react';
import {CharsetSelector, HtmlSelector} from "../components";
import {msQuery} from "../functions";

export function Actions(props) {
    
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
            if (data.status === true && mode === 'tableRename') {
                window.location = '?s=tbl_data&table=' + form.querySelector('[name="newName"]').value
            }
        })
    }


    return (
      <Fragment>

          <fieldset className="msGeneralForm">
              <legend>Переименовать таблицу в:</legend>
              <form>
                  <input name="newName" type="text" onChange={onChangeRenameName} required value={renameName}/>
                  <input type="button" value="Выполнить!" onClick={tableAction.bind(this, "tableRename")}
                         disabled={!renameName} className="submit"/>
              </form>
          </fieldset>

          <fieldset className="msGeneralForm">
              <legend>Переместить таблицы в (база данных.таблица):</legend>
              <form>
                  <HtmlSelector data={props.dbs} name="newDB" auto="false" value={props.db}/>
                  .
                  <input name="newName" required type="text" defaultValue={props.table}/>
                  <input type="submit" onClick={tableAction.bind(this, "tableMove")} value="Выполнить!"
                         className="submit"/>
              </form>
          </fieldset>

          <fieldset className="msGeneralForm">
              <legend>Скопировать таблицу в (база данных.таблица):</legend>
              <form>
                  <HtmlSelector data={props.dbs} value={props.db} name="newDB"/>
                  .
                  <input name="newName" type="text" required defaultValue={props.table}/>
                  <div className="mt-10">
                      <input type="submit" onClick={tableAction.bind(this, "tableCopyTo")} value="Выполнить!"/>
                      <input type="checkbox" name="tableCopyNoData" value="1"/> только структуру
                  </div>
              </form>
          </fieldset>

          {props.charsets && Object.keys(props.charsets).length > 0 ? <fieldset className="msGeneralForm">
              <legend>Изменить кодировку таблицы</legend>
              <form>
                  <CharsetSelector charsets={props.charsets} value={props.charset}/>
                  <input type="button" onClick={tableAction.bind(this, "tableCharset")} value="Выполнить!"
                         className="ml-10"/>
              </form>
          </fieldset> : null}

          <fieldset className="msGeneralForm">
              <legend>Комментарий к таблице</legend>
              <form>
                  <input name="comment" type="text" size="60" onChange={onChangeComment} defaultValue={comment}/>
                  <input type="submit" onClick={tableAction.bind(this, "tableComment")} value="Выполнить!"
                         disabled={!comment} className="submit"/>
              </form>
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>Изменить порядок</legend>
              <form>
                  <HtmlSelector data={props.fields} name="field"/>
                  <select name="order" className="ml-10">
                      <option value="">По возрастанию</option>
                      <option value="DESC">По убыванию</option>
                  </select>
                  <input type="submit" onClick={tableAction.bind(this, "tableOrder")} value="Выполнить!"
                         className="ml-10"/>
              </form>
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>Опции таблицы</legend>
              <form>
                  <input name="auto_increment" type="text" size="3" defaultValue={props.ai}/> auto_increment
                  <input type="submit" onClick={tableAction.bind(this, "tableOptions")} value="Выполнить!"
                         className="submit"/>
              </form>
          </fieldset>

          <div className="globalMenu mb-20">
              <a onClick={tableAction.bind(this, "tableCheck")} href="#">Проверить таблицу</a>
              <a onClick={tableAction.bind(this, "tableAnalize")} href="#">Анализ таблицы</a>
              <a onClick={tableAction.bind(this, "tableRepair")} href="#">Починить таблицу</a>
              <a onClick={tableAction.bind(this, "tableOptimize")} href="#">Оптимизировать таблицу</a>
              <a onClick={tableAction.bind(this, "tableFlush")} href="#">Сбросить кэш таблицы ("FLUSH")</a>
          </div>
      </Fragment>
    );
}