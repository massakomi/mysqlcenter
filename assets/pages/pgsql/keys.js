import {msQuery} from "../../functions";
import React from "react";
import PropTypes from "prop-types";

export function PgKeysInfo(props) {

    const deleteKey = (query, e) => {
        e.preventDefault();
        if (!confirm('Подтвердите')) {
            return false
        }
        msQuery('deleteKey', query, () => {
            e.target.closest('tr').remove()
        })
    }

    return (
      <table className="contentTable">
          <thead>
          <tr>
              <th></th>
              <th>Таблица</th>
              <th>Не уникальное</th>
              <th>Ключ</th>
              <th>Номер</th>
              <th>Колонка</th>
          </tr>
          </thead>
          <tbody>
          {props.dataKeys.map((key) => {
              let query = `?key=${key.Key_name}&field=${key.Column_name}`
              return (
                <tr key={key.Key_name + key.Seq_in_index}>
                    <td><a href="#" onClick={deleteKey.bind(this, query)}><img src={props.dirImage + "close.png"} alt="" border="0"/></a></td>
                    {Object.values(key).slice(0, 5).map((value, key) =>
                      <td key={key + "index"}>{value}</td>
                    )}
                </tr>
              )
          })}
          </tbody>
      </table>
    )
}

PgKeysInfo.propTypes = {
    dataKeys: PropTypes.array
};