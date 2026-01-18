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
        data = await msQuery('querysql', {sql: 'SELECT * FROM pg_sequences'})
        setSequences(data)
    }

    const onDeleteSchema = (data) => {
        msQuery('querysql', {sql: `DROP SCHEMA ${data.schema_name}`})
    }

    const onDeleteSequence = (data) => {
        msQuery('querysql', {sql: `DROP SEQUENCE ${data.sequencename}`})
    }

    const onEditSequence = (data) => {
      let nextVal = prompt('Изменение nextval sequence:', data.start_value)
      if (nextVal > 0) {
        msQuery('querysql', {type:'exec', sql: `SELECT setval('${data.sequencename}', ${nextVal});`})
      }
    }

    const [schemas, setSchemas] = useState([]);
    const [sequences, setSequences] = useState([]);
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

          <Table data={schemas} onDelete={onDeleteSchema} />
          <Table data={sequences} onDelete={onDeleteSequence} onEdit={onEditSequence} />
      </Fragment>
    );
}