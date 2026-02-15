import React from 'react';
import {msQuery} from "../functions";

export function Config({ data = [] }) {
  const onSubmit = async (e) => { e.preventDefault(); await msQuery('update', e.currentTarget); };
  const onRestore = async () => { await msQuery('restore'); };

  const rows = data.map(({ name, title, type, value }) => (
    <tr key={name}>
      <td>{title}</td>
      <td>
        {type.includes('boolean')
          ? <input type="checkbox" name={name} defaultChecked={Boolean(Number(value))} />
          : <input type="text" name={name} defaultValue={value} />}
      </td>
    </tr>
  ));

  return (
    <form className="tableFormEdit" onSubmit={onSubmit}>
      <table id="tableFormEdit">
        <thead>
        <tr>
          <th>Параметр</th>
          <th>Значение</th>
        </tr>
        </thead>
        <tbody>{rows}</tbody>
      </table>
      <input type="submit" value="Изменить" className="submit" />
      <p style={{textAlign: 'center'}}><input type="button" onClick={onRestore} value="Восстановить значения по умолчанию" /></p>

    </form>
  );
}