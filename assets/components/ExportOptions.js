import React, {Fragment} from "react";
import {HtmlSelector} from "../components";

export function ExportOptions(props) {

    const image = (src) => {
        return props.dirImage + src
    }

    const isMySQL = props.db_type === 'mysql' || props.db_type === 'mysqli' || props.db_type === 'pdo_mysql';
    const isPostgreSQL = props.db_type === 'pgsql' || props.db_type === 'pdo_pgsql';

    return (
      <div className="options">
          <div>
              <label htmlFor="f1"><input type="checkbox" value="1" name="export_struct" id="f1"
                                         defaultChecked={props.structChecked}/> Структура</label>

              <label htmlFor="f2" title="Укажите эту опцию, если вы хотите заменить таблицу (команда DROP TABLE)">
                  <input type="checkbox" value="1" className="l2" name="addDrop" id="f2"/>
                  Добавить удаление таблицы
              </label>

              {isMySQL && (
                  <label htmlFor="f3" title="Отключить индексы на время вставки данных">
                      <input type="checkbox" value="1" className="l2" name="disableKeys" id="f3"/>
                      Отключить индексы (DISABLE KEYS)
                  </label>
              )}

              <label htmlFor="f4" title="Шапка к дампу с информацией о версиях ПО, а также заголовки таблиц">
                  <input type="checkbox" value="1" name="addComment" className="l2" id="f4" defaultChecked/>
                  Добавлять комментарии
              </label>

              {isMySQL && (
                  <>
                      <label htmlFor="f5" title="Экспортировать хранимые процедуры и функции">
                          <input type="checkbox" value="1" className="l2" name="routines" id="f5" defaultChecked/>
                          Рутины (процедуры и функции)
                      </label>

                      <label htmlFor="f6" title="Экспортировать триггеры">
                          <input type="checkbox" value="1" className="l2" name="triggers" id="f6" defaultChecked/>
                          Триггеры
                      </label>

                      <label htmlFor="f7" title="Экспортировать события">
                          <input type="checkbox" value="1" className="l2" name="events" id="f7"/>
                          События
                      </label>
                  </>
              )}

              {isPostgreSQL && (
                  <label htmlFor="f8" title="Отключить триггеры на время вставки данных">
                      <input type="checkbox" value="1" className="l2" name="disableTriggers" id="f8"/>
                      Отключить триггеры
                  </label>
              )}

              <label htmlFor="f9"><input name="export_to" id="f9" type="radio" value="1"/> в архив</label>
              <label htmlFor="f10"><input name="export_to" id="f10" type="radio" value="2" defaultChecked/> в
                  текст</label>
          </div>
          <div>
              <label htmlFor="f11"><input type="checkbox" value="1" name="export_data" id="f11" defaultChecked/>Данные</label>

              <label htmlFor="f12">
                  <input type="checkbox" value="1" className="l2" name="insExpand" id="f12"/>
                  Одним запросом
              </label>

              {isMySQL && (
                  <label htmlFor="f13">
                      <input type="checkbox" value="1" className="l2" name="insIgnor" id="f13"/>
                      IGNORE
                  </label>
              )}

              {isPostgreSQL && (
                  <label htmlFor="f14">
                      <input type="checkbox" value="1" className="l2" name="columnInserts" id="f14"/>
                      Вставка по колонкам (COLUMN INSERTS)
                  </label>
              )}

              Тип экспорта
              <select name="export_option">
                  <option>INSERT</option>
                  <option>REPLACE</option>
              </select>
          </div>

      </div>
    )
}