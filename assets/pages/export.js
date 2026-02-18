import React, {Fragment} from 'react';
import {forElements, umaker} from "../functions";
import {ExportOptions} from "../components/ExportOptions";

function Form(props) {

    const msMultiSelect = (event) => {
        forElements('[name="'+props.selectMultName+'"] option', function() {
            if (event.target.classList.contains('invert')) {
                this.selected = !this.selected
            } else {
                this.selected = event.target.classList.contains('select')
            }
        })
    }


    return (
      <form action="" method="post" name="formExport">
          <div className="tableExport">
              <div>
                  <select name={props.selectMultName} multiple="multiple" className="sel" defaultValue={props.optionsSelected}>
                      {props.optionsData.map((v) =>
                        <option key={v.toString()}>{v}</option>
                      )}
                  </select><br />
                  <a href="#" onClick={msMultiSelect} className="hs select">все</a>
                  <a href="#" onClick={msMultiSelect} className="hs unselect">очистить</a>
                  <a href="#" onClick={msMultiSelect} className="hs invert">инверт</a>
              </div>
              <div>
                  <ExportOptions fields={props.fields} dirImage={props.dirImage} structChecked={props.structChecked} db_type={props.db_type} />

                  WHERE условие<br />
                  <input name="export_where" type="text" defaultValue={props.whereCondition} style={{width:'95%', display:'block', margin:'10px 0'}} />
                  <input type="submit" value="Экспортировать!" />
              </div>
          </div>
          <a href={umaker({s: 'export', 'mode': 'special'})}>Специальный экспорт</a>
      </form>
    );
}

function Results(props) {
    return <textarea name="export" rows="40" wrap="off" className="sql" defaultValue={props.content}></textarea>
}

export function Export(props) {
    return (
      <Fragment>
          {props.content ? <Results content={props.content} /> : <Form {...props} />}
      </Fragment>
    )
}