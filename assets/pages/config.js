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

    const trs = Object.values(props.data).map((item) => {
        if (!item.includes('|')) {
            return;
        }
        let [name, title, value, type] = item.split('|')
        let input = ''
        if (type.includes('boolean')) {
            input = <input type="checkbox" name={name} value="1" defaultChecked={value !== '0'} />
        } else {
            input = <input type="text" name={name} defaultValue={value} />
        }
        return (
          <tr key={item.toString()}>
              <td>{title}</td>
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