import React from 'react';
import {msQuery} from "../functions";

export function Config(props) {

    const update = async (e) => {
        e.preventDefault()
        await msQuery('configUpdate', e.target.parentNode)
    }

    const restore = async () => {
        await msQuery('configRestore', '')
    }

    const trs = props.data.map((item) => {
        let input = ''
        if (item.type.includes('boolean')) {
            input = <input type="checkbox" name={item.name} value="1" defaultChecked={item.value !== 0} />
        } else {
            input = <input type="text" name={item.name} defaultValue={item.value} />
        }
        return (
          <tr key={item.title}>
              <td>{item.title}</td>
              <td>{input}</td>
          </tr>
        )

    });

    return (
      <form className="tableFormEdit">
          <table id="tableFormEdit">
              <thead>
              <tr>
                  <th>Параметр</th>
                  <th>Значение</th>
              </tr>
              </thead>
              <tbody>{trs}</tbody>
          </table>
          <input type="button" onClick={update.bind(this)} value="Изменить" className="submit"/>
          <p style={{textAlign: 'center'}}><input type="button" onClick={restore} value="Восстановить значения по умолчанию" /></p>

      </form>
    );    
}