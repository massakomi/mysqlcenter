import React, {useState, useEffect, useRef, Fragment} from 'react';
import {Table} from "../components";
import {empty, forElements} from "../functions";
import {SearchTable} from "./searchTable";

function SearchDatabase(props) {

    const [query, setQuery] = useState('');
    const [queryField, setQueryField] = useState('');
    const [disabled, setDisabled] = useState(true);

    const updateState = (event) => {
        const name = event.target.name;
        const value = event.target.value;
        if (name === 'query') {
            setQuery(event.target.value)
            setDisabled(!value && !queryField)
        }
        if (name === 'queryField') {
            setQueryField(event.target.value)
            setDisabled(!value && !query)
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
 
    return (
      <form action="/?s=search" method="post" name="formSearch">
          <table className="tableExport">
              <tbody><tr>
                  <td valign="top">
                      <select name="table[]" multiple className="sel" defaultValue={props.tables}>
                          {Object.values(props.tables).map((table) =>
                            <option key={table.toString()}>{table}</option>
                          )}
                      </select>   <br />
                      <a href="#" onClick={msMultiSelect} className="hs select">все</a> &nbsp;
                      <a href="#" onClick={msMultiSelect} className="hs unselect">очистить</a> &nbsp;
                      <a href="#" onClick={msMultiSelect} className="hs invert">инверт</a>
                  </td>
                  <td valign="top">
                      искать по всем полям    <br />
                      <input name="query" ref={queryAll} type="text" size="50" onChange={updateState} defaultValue={query} /><br />
                      искать имя поля    <br />
                      <input name="queryField" type="text" size="50" onChange={updateState} defaultValue={queryField} /><br /> <br />
                      <input type="submit" defaultValue="Искать!" className="submit" disabled={disabled} />
                  </td>
              </tr></tbody>
          </table>
      </form>
    ); 
}

export function Search(props) {

    return (
      <Fragment>
          {props.results ? <Table data={props.results} /> : null}
          {empty(props.table) ? <SearchDatabase {...props} /> : <SearchTable {...props} /> }
      </Fragment>
    )
}