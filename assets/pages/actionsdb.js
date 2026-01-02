import React from 'react';
import {Fragment} from "react";
import {CharsetSelector} from "../components";
import {empty} from "../functions";
import {Table} from "../components/Table";

function FieldSet(props) {
    return (
      <fieldset className="msGeneralForm">
          <legend>{props.title}</legend>
          <form action={props.url + "&action=" + props.action} method="post" name={props.action}>
              {props.children}
              <input type="submit" value="Выполнить!" style={{marginLeft: '5px'}} />
          </form>
      </fieldset>
    )
}

export function Actionsdb(props) {

    return (
      <Fragment>
          <FieldSet title="Переименовать базу данных в:" action="dbRename" {...props}>
              <input name="newName" type="text" required defaultValue={props.db}/>
          </FieldSet>
          <FieldSet title="Копировать базу данных в:" action="dbCopy" {...props}>
              <input name="newName" required type="text" defaultValue={props.db + "_copy"}/><br/>
              <input name="option" type="radio" value="struct"/> Только структуру <br/>
              <input name="option" type="radio" value="all" defaultChecked/> Структура и данные <br/>
              <input name="option" type="radio" value="data"/> Только данные <br/>
              <input name="switch" type="checkbox" value="1"/> Перейти к скопированной БД <br/><br/>
          </FieldSet>
          {!empty(props.charsets) ? <FieldSet title="Изменить кодировку базы данных:" action="dbCharset" {...props}>
              <CharsetSelector charsets={props.charsets}/>
          </FieldSet> : null}
      </Fragment>
    );

}