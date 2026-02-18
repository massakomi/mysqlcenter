import React from 'react';
import {msQuery} from "../functions";

export function Config({ data = [] }) {
  const onSubmit = async (e) => { e.preventDefault(); await msQuery('update', e.currentTarget); };
  const onRestore = async () => {
    if (window.confirm('Вы уверены, что хотите восстановить значения по умолчанию?')) {
      await msQuery('restore');
    }
  };

  const getInputField = (name, type, value) => {
    if (type.includes('boolean')) {
      return <input type="checkbox" id={name} name={name} defaultChecked={Boolean(Number(value))} value="1" />;
    } else if (type.includes('int')) {
      return <input type="number" id={name} name={name} defaultValue={value} />;
    } else {
      return <input type="text" id={name} name={name} defaultValue={value} />;
    }
  };

  const rows = data.map(({ name, title, type, value }) => (
    <div key={name}>
      <div className="right">
        {getInputField(name, type, value)}
      </div>
      <label htmlFor={name}>{title}</label>
    </div>
  ));

  return (
    <form className="config mt-10" onSubmit={onSubmit}>
      <div className="rows">
        {rows}
      </div>

      <div className="flex mt-20 mb-20 justify-sb">
        <div>
          <input type="submit" value="Изменить" className="submit" />
        </div>
        <div>
          <input type="button" onClick={onRestore} value="Восстановить значения по умолчанию" />
        </div>
      </div>
    </form>
  );
}