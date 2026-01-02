import React, {Fragment, useEffect, useState} from "react";
import {msQuery} from "../../functions";

import {Table} from "../../components/Table";

export function TblListPostgresSQL() {
    if (window.driver !== 'pgsql') {
        return null
    }

    const loadAll = async () => {
        let data = await msQuery('querysql', {sql: 'SELECT schema_name FROM information_schema.schemata'})
        setSchemas(data)
    }

    const onDelete = (data) => {
        msQuery('querysql', {sql: `DROP SCHEMA ${data.schema_name}`})
    }

    const [schemas, setSchemas] = useState([]);
    useEffect(() => {
        loadAll()
    }, []);

    const onSubmit = (event) => {
        msQuery('schemaAdd', event.target)
    }

    return (
      <Fragment>
          <fieldset className="inline">
              <legend>Создать схему в базе {window.db}</legend>
              <form method="post" onSubmit={onSubmit}>
                  <input name="name" type="text"/>
                  <button type="submit" className="ml-10">Создать!</button>
              </form>
          </fieldset>

          <Table data={schemas} onDelete={onDelete} />
      </Fragment>
    );
}