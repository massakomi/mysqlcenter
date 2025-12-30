import React from 'react';
import {Fragment, useEffect, useState} from "react";
import {CharsetSelector, Table} from "../components";
import {msQuery} from "../MysqlCenter";

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

function MysqlProcessList(props) {

    function kill(id) {
        msQuery('killProcess', `id=${id}`)
    }

    let trs = []
    for (let key in props.processes) {
        let item = props.processes[key]
        trs.push(
          <tr key={`tr-${key}`}>
              <td><a href="#" onClick={kill.bind(this, item.Id)}>Kill</a></td>
              <td>{item.Id}</td>
              <td>{item.User}</td>
              <td>{item.Host}</td>
              <td>{item.db ? item.db : <i>нет</i>}</td>
              <td>{item.Command}</td>
              <td>{item.Time}</td>
              <td>{item.State ? item.State : '---'}</td>
              <td>{item.Info ? item.Info : '---'}</td>
          </tr>)
    }

    return (
      <table className="contentTable">
          <thead>
          <tr>
              <th></th>
              <th>td</th>
              <th>user</th>
              <th>host</th>
              <th>db</th>
              <th>command</th>
              <th>time</th>
              <th>status</th>
              <th>sqlQuery</th>
          </tr>
          </thead>
          <tbody>
          {trs}
          </tbody>
      </table>
    )
}



function Server_variables() {

    const [sessionVars, setSessionVars] = useState([])
    const [globalVars, setGlobalVars] = useState([])

    const loadAll = async () => {
        let sql = 'SHOW SESSION VARIABLES';
        let mode = 'querysql'
        let type = 'pair-value'
        let sessionVarsNew = await msQuery(mode, {sql, type})
        //console.log(sessionVars)
        sql = 'SHOW GLOBAL VARIABLES';
        mode = 'querysql'
        type = 'pair-value'
        let globalVarsNew = await msQuery(mode, {sql, type})
        setSessionVars(sessionVarsNew)
        setGlobalVars(globalVarsNew) 
    }

    const wrap = (s, cmp) => {
        if (s === undefined || cmp === s) {
            return null
        } else {
            return <span title={s}>{s.substring(0, 20)}</span>
        }

    }
    
    useEffect(() => {
        loadAll()
    }, []);

    let trs = []
    let i =0;
    for (let prop in sessionVars) {
        i ++
        trs.push((
          <tr key={i}>
              <td><b>{prop.replace('_', ' ')}</b></td>
              <td>{wrap(sessionVars[prop])}</td>
              <td>{wrap(globalVars[prop], sessionVars[prop])}</td>
          </tr>
        ))
    }

    return (
      <table className="contentTable">
          <thead>
          <tr>
              <th>Свойство</th>
              <th>session var</th>
              <th>global var</th>
          </tr>
          </thead>
          <tbody>
          {trs}
          </tbody>
      </table>
    );
}


function UserInfo(props) {
    return (
      <Fragment>
          <fieldset className="msGeneralForm">
              <legend>Пользователи</legend>
              <Table data={props.users} />
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>SHOW GRANTS</legend>
              <div className="mb-5">Список привилегий, предоставленных аккаунту, который вы используете для соединения с сервером (FOR CURRENT_USER)</div>
              <Table data={props.grants} />
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>SHOW PRIVILEGES</legend>
              <div className="mb-5">Список системных привилегий, которые поддерживает MySQL сервер. Точный список привилегий зависит от версии вашего сервера.</div>
              <Table data={props.privileges} />
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>SHOW ENGINES</legend>
              <div className="mb-5">SHOW ENGINES displays status information about the server\'s storage engines. This is particularly useful for checking whether a storage engine is supported, or to see what the default engine is</div>
              <Table data={props.engines} />
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>Переменные сервера</legend>
              <Server_variables />
          </fieldset>
      </Fragment>
    )
}

export function Actionsdb(props) {

    const operations = (
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
          {props.charsets.length ? <FieldSet title="Изменить кодировку базы данных:" action="dbCharset" {...props}>
              <CharsetSelector charsets={props.charsets}/>
          </FieldSet> : null}
          <fieldset className="msGeneralForm">
              <legend>Список процессов</legend>
              {window.driver === 'pgsql' ? <Table data={props.processes} /> : <MysqlProcessList processes={props.processes} url={props.url}/>}
          </fieldset>
      </Fragment>
    )

    return (
      <Fragment>
          {props.users ? <UserInfo {...props} />
            : operations}
      </Fragment>
    );

}