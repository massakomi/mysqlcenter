import React from 'react';
import {addRow, removeRow} from "../MysqlCenter";
export function Tbl_key_add(props) {

    let types = ['PRIMARY KEY','INDEX','UNIQUE','FULLTEXT']

    return (
      <form method="post" action="" className="tableFormEdit">

          имя индекса:
          <input type="text" required name="keyName" defaultValue={props.keyName} /><br /><br />
          тип индекса:
          <select name="keyType" value={props.keyType}>
              {types.map((t) =>
                <option key={t}>{t}</option>
              )}
          </select>

          <br /><br />

          <img src={`${props.dirImage}nolines_plus.gif`} alt="" border="0" onClick={addRow.bind(this, 'tableFormEdit', 'last')} title="Добавить поле" style={{cursor: 'pointer'}}  />
          <img src={`${props.dirImage}nolines_minus.gif`} alt="" border="0" onClick={removeRow.bind(this, 'tableFormEdit')}  title="Удалить поле" style={{cursor: 'pointer'}} /><br />

          <table id="tableFormEdit">
              <tbody>
              <tr>
                  <th>Поле</th>
                  <th>Размер</th>
              </tr>
              <tr>
                  <td>
                      <select name="field[]">
                          {Object.keys(props.fieldRows).map((field) =>
                            <option value={field} key={field}>{props.fieldRows[field]}</option>
                          )}
                      </select>
                  </td>
                  <td><input type="text" name="length[]" size="10" /></td>
              </tr></tbody>
          </table>

          <input type="submit" value="Выполнить" className="submit" />
      </form>
    );
}
