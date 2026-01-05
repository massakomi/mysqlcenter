import React, {Fragment, useEffect, useState} from "react";
import {Table} from "../../components/Table";
import {msQuery} from "../../functions";

export function PostgresSQLInfo() {

    const [settings, setSettings] = useState([])
    const [values, setValues] = useState({})

    const loadAll = async () => {

        let data = await msQuery('pgLoadSettings')
        setSettings(data.page)

        let allValues = await msQuery('querysql', {sql: 'SHOW ALL'})
        let valuesDict = {}
        for (let item of allValues) {
            valuesDict [item.name] = item.setting
        }

        setValues(valuesDict)

        console.log(valuesDict)
    }

    useEffect(() => {
        loadAll()
    }, []);

    let rows = []
    for (let item of settings) {
        let paramsEx = []
        for (let param of item.params) {
            paramsEx.push({
                'name': {
                    'text': param.name,
                    'title': param.desc
                },
                'value': {
                    'text': values[param.name] || '',
                    'title': param.type
                }
            })
        }
        rows.push(
          <div>
              <h2 key={item.name.toString()} className="mb-5">{item.name}</h2>
              <Table className="pg-info" data={paramsEx}/>
          </div>
        )
    }

    return (
      <Fragment>
          <a href="https://postgrespro.ru/docs/postgresql/current/runtime-config">PostgreSQL help</a>
          {rows}
      </Fragment>
    );
}