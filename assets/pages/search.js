import React, {useState, useEffect, useRef, Fragment} from 'react';
import {empty, forElements} from "../functions";
import {SearchTable} from "./searchTable";
import {Table} from "../components/Table";

function SearchDatabase(props) {

    const [query, setQuery] = useState(window.post.query || '');
    const [mode, setMode] = useState('');
    const [queryField, setQueryField] = useState(window.post.queryField || '');
    const [disabled, setDisabled] = useState(true);

    const updateState = (event) => {
        const name = event.target.name;
        const value = event.target.value;
        if (name === 'query') {
            setQuery(event.target.value)
            setQueryField('')
            setDisabled(!value && !queryField)
            setMode('searchDb')
        }
        if (name === 'queryField') {
            setQueryField(event.target.value)
            setQuery('')
            setDisabled(!value && !query)
            setMode('searchDbField')
        }
    }

    const msMultiSelect = event => {
        forElements('[name="table[]"] option', function() {
            if (event.target.classList.contains('invert')) {
                this.selected = !this.selected
            } else {
                this.selected = event.target.classList.contains('select')
            }
        })
    };

    let queryAll = useRef(null);
    useEffect(() => {
        queryAll.current.focus()
    }, []);

    const size = Math.min(40, Math.max(10, props.tables.length))

    return (
      <form action="/?s=search" method="post" className="flex mt-10">
          <input type="hidden" name="mode" defaultValue={mode}/>
          <div>
              <select name="table[]" multiple className="block mb-5" defaultValue={props.tables} size={size}>
                  {Object.values(props.tables).map((table) =>
                    <option key={table.toString()}>{table}</option>
                  )}
              </select>
              <a href="#" onClick={msMultiSelect} className="hs select mr-10">все</a>
              <a href="#" onClick={msMultiSelect} className="hs unselect mr-10">очистить</a>
              <a href="#" onClick={msMultiSelect} className="hs invert">инверт</a>
          </div>
          <div>
              <div className="mb-10">
                  искать по всем полям <br/>
                  <input name="query" ref={queryAll} type="text" size="50" onChange={updateState} value={query}/>
              </div>
              <div className="mb-10">
                  искать имя поля <br/>
                  <input name="queryField" type="text" size="50" onChange={updateState} value={queryField}/>
              </div>

              <input type="submit" defaultValue="Искать!" className="submit" disabled={disabled}/>
          </div>
      </form>
);
}

export function Search(props) {

    return (
      <Fragment>
          {props.results ? <Table data={props.results} /> : null}
          {!empty(props.table) ? <SearchTable {...props} /> : null }
          <SearchDatabase {...props} />
      </Fragment>
    )
}