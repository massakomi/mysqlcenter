import {msQuery} from "../../functions";
import React from "react";

export function MysqlKeysInfo(props) {

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
              <th><span
                title="Сортировка колонки в ключе. В MySQL, значение ‘A’ (по возрастанию) или NULL (без сортировки)">Сортировка</span>
              </th>
              <th><span
                title="Приблизительное число уникальных значений в индексе. Это поле обновляется при запуске  ANALYZE TABLE или myisamchk -a. Cardinality расчитывается на основе цифровой статистики, поэтому его значение не обязательно будет точным даже для небольших таблиц. Чем выше cardinality, тем больше шансов, что MySQL будет применять индекс в операциях объединения (JOIN)">Cardinality</span>
              </th>
              <th><span
                title="Количество индексированных символов, если колонка только частично индексирована, NULL если вся колонка индексирована">Sub_part</span>
              </th>
              <th><span
                title="Как упакован ключ. NULL если не упакован. Хранение значений в сжатом (упакованном) виде используется, если в индексе присутствуют поля, у которых переменная длина">Packed</span>
              </th>
              <th><span
                title="YES если колонка может содержать NULL. Если нет, то поле содержит NO после MySQL 5.0.3, и '' в предыдущих версиях">Null</span>
              </th>
              <th><span
                title="Метод индексирования (BTREE - если длина полей индекса не превышает 10 байт, HASH - хранение значений как хэш кодов. Используется, если индекс составной, его длина больше одной восьмой от размера страницы БД или же больше, чем 256 байт, FULLTEXT, RTREE)">Тип индекса</span>
              </th>
              <th>Комментарий</th>
              <th>Index_comment</th>
              <th>Visible</th>
              <th>Expression</th>
          </tr>
          </thead>
          <tbody>
          {props.dataKeys.map((v) => {
              let query = `?key=${v.Key_name}&field=${v.Column_name}`
              return (
                <tr key={v.Key_name + v.Seq_in_index}>
                    <td><a href="#" onClick={deleteKey.bind(this, query)}><img src={props.dirImage + "close.png"} alt="" border="0"/></a></td>
                    {Object.values(v).map((value, key) =>
                      <td key={key + "index"}>{value}</td>
                    )}
                </tr>
              )
          })}
          </tbody>
      </table>
    )
}