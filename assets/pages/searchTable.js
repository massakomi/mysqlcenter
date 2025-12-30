import React from 'react';
import {HtmlSelector} from "../components";
import {umaker} from "../MysqlCenter";
export function SearchTable(props) {

    const sendGet = (e) => {
        e.preventDefault();
        window.location = e.target.action + '&where=' + e.target.elements[0].value;
    }

    const onFunctionChange = (e) => {
        document.querySelector('#where').value += e.target.options[e.target.selectedIndex].text + ' '
    }
 

    let fields = props.fields;
    fields.unshift('[поля]')
    let opers = ['[операнды]', ' = ', ' != ', ' < ', ' > ', 'IS NULL',
        ' LIKE "%%" ', ' LIKE "%" ', ' LIKE "" ', ' NOT LIKE "" ',
        ' REGEXP "^fo" '];
    let funcs = ['[функции]', 'UPPER()', 'LOWER()', 'TRIM()', 'SUBSTRING()', 'REPLACE()', 'REPEAT()']

    return (
      <div>
          <fieldset className="msGeneralForm">
              <legend>Добавить к условию WHERE</legend>
              <form action={umaker({s: 'tbl_data'})} method="get" onSubmit={sendGet}>
                  <input name="where" type="text" id="where" className="w95" />
                  <input type="submit" value="Выполнить!" className="submit mr10" style={{display: 'inline'}} />
                  <span className="mr10">вставить</span>

                  <HtmlSelector data={fields} onChange={onFunctionChange} />
                  <HtmlSelector data={opers} onChange={onFunctionChange} />
                  <HtmlSelector data={funcs} onChange={onFunctionChange} />

              </form>
          </fieldset>

          <fieldset className="msGeneralForm">
              <legend>Найти и заменить</legend>
              <form className="tableFormEdit">
                  <table id="tableFormEdit" style={{'width': '100%'}}>
                      <tbody>
                      <tr>
                          <td width="100">Найти</td>
                          <td><input name="search_for" className="w95" type="text" defaultValue={props.search_for}/></td>
                      </tr>
                      <tr>
                          <td width="100">Заменить</td>
                          <td><input name="replace_in" className="w95" type="text" defaultValue={props.replace_in}/></td>
                      </tr>
                      <tr>
                          <td width="100">Поле</td>
                          <td><HtmlSelector data={props.fields} name="field"/></td>
                      </tr>
                      </tbody>
                  </table>
                  <input type="submit" value="Выполнить" className="submit"/>
              </form>
          </fieldset>
      </div>
    );
}