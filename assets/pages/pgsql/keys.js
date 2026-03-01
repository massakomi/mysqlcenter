import {msQuery} from "../../functions";
import React from "react";
import PropTypes from "prop-types";

export function PgKeysInfo(props) {
  const deleteKey = (query, e) => {
    e.preventDefault();
    if (!confirm('Подтвердите')) {
      return false;
    }
    msQuery('deleteKey', query, () => {
      e.target.closest('tr').remove();
    });
  };

  // Группировка по Index_name
  const groupedKeys = props.dataKeys.reduce((acc, key) => {
    if (!acc[key.Index_name]) {
      acc[key.Index_name] = [];
    }
    acc[key.Index_name].push(key);
    return acc;
  }, {});

  return (
    <div>
      {Object.entries(groupedKeys).map(([indexName, keys]) => (
        <div key={indexName} style={{ marginBottom: '20px' }}>
          <h3>{indexName}</h3>
          <table className="contentTable">
            <thead>
            <tr>
              <th></th>
              <th>Не уникальное</th>
              <th>Ключ</th>
              <th>Номер</th>
              <th>Колонка</th>
            </tr>
            </thead>
            <tbody>
            {keys.map((key) => {
              let query = `?key=${key.Key_name}&field=${key.Column_name}`;
              return (
                <tr key={key.Key_name + key.Seq_in_index}>
                  <td><a href="#" onClick={deleteKey.bind(this, query)}><img src={props.dirImage + "close.png"} alt="" border="0"/></a></td>
                  <td>{key.Non_unique}</td>
                  <td>{key.Key_name}</td>
                  <td>{key.Seq_in_index}</td>
                  <td>{key.Column_name}</td>
                </tr>
              );
            })}
            </tbody>
          </table>
        </div>
      ))}
    </div>
  );
}


PgKeysInfo.propTypes = {
    dataKeys: PropTypes.array
};