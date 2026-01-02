import React, {Fragment, useEffect, useState} from 'react';
import {msQuery} from "../../functions";

import {Table} from "../../components/Table";

export function MysqlInfo(props) {
    return (
      <div>
           <MysqlServerInfo {...props} />
      </div>
    );
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
            if (typeof(s) === 'string') {
                return <span title={s}>{s.substring(0, 20)}</span>
            } else {
                return s
            }
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


function MysqlServerInfo(props) {

    const [engines, setEngines] = useState([])

    const loadAll = async () => {
        let engines = await msQuery('querysql', {sql: 'SHOW ENGINES'})
        setEngines(engines)
    }

    useEffect(() => {
        loadAll()
    }, []);

    return (
      <Fragment>
          <fieldset className="msGeneralForm">
              <legend>SHOW ENGINES</legend>
              <div className="mb-5">SHOW ENGINES displays status information about the server\'s storage engines.
                  This is particularly useful for checking whether a storage engine is supported,
                  or to see what the default engine is
              </div>
              <Table data={engines}/>
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>Список процессов</legend>
              {window.driver === 'pgsql' ? <Table data={props.processes}/> :
                <MysqlProcessList processes={props.processes} url={props.url}/>}
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>Переменные сервера</legend>
              <Server_variables/>
          </fieldset>
      </Fragment>
    )
}