import React, {Fragment} from "react";
import {HtmlSelector} from "../components";

export function ExportOptions(props) {

    const image = (src) => {
        return props.dirImage + src
    }

    return (
      <div className="options">
          <div>
              <label htmlFor="f1"><input type="checkbox" value="1" name="export_struct" id="f1"
                                         defaultChecked={props.structChecked}/> Структура</label>

              <label htmlFor="f2" title="Укажите эту опцию, если вы хотите заменить таблицу (команда DROP TABLE)">
                  <input type="checkbox" value="1" className="l2" name="addDrop" id="f2"/>
                  Добавить удаление таблицы
              </label>

              <label htmlFor="f3"
                     title="Будут преобразованы команды: CREATE TABLE IF NOT EXISTS... и при удалении таблиц DROP TABLE IF EXISTS ...">
                  <input type="checkbox" value="1" className="l2" name="addIfNot" id="f3"/>
                  Добавить IF NOT EXISTS
              </label>

              <label htmlFor="f4" title="К каждой таблице будет добавлено AUTO_INCREMENT=текущее значение">
                  <input type="checkbox" value="1" className="l2" name="addAuto" id="f4"/>
                  Добавить значение AUTO_INCREMENT
              </label>

              <label htmlFor="f5" title="Оставьте эту опцию, чтобы быть уверенным, что всё пройдет гладко">
                  <input type="checkbox" value="1" className="l2" name="addKav" id="f5" defaultChecked/>
                  Обратные `кавычки` в названиях таблиц и полей
              </label>

              <label htmlFor="f12" title="Шапка к дампу с информацией о версиях ПО, а также заголовки таблиц">
                  <input type="checkbox" value="1" name="addComment" id="f12" defaultChecked/>
                  Добавлять комментарии
              </label>

              <label htmlFor="f13"><input name="export_to" id="f13" type="radio" value="1"/> в архив</label>
              <label htmlFor="f14"><input name="export_to" id="f14" type="radio" value="2" defaultChecked/> в
                  текст</label>
          </div>
          <div>
              <label htmlFor="f6"><input type="checkbox" value="1" name="export_data" id="f6" defaultChecked/>Данные</label>

              <label htmlFor="f7">
                  <input type="checkbox" value="1" className="l2" name="insFull" id="f7" defaultChecked/>
                  Указать все поля
              </label>

              <label htmlFor="f8">
                  <input type="checkbox" value="1" className="l2" name="insExpand" id="f8"/>
                  Одним запросом
              </label>

              <label htmlFor="f9">
                  <input type="checkbox" value="1" className="l2" name="insZapazd" id="f9"/>
                  DELAYED
              </label>

              <label htmlFor="f10">
                  <input type="checkbox" value="1" className="l2" name="insIgnor" id="f10"/>
                  IGNORE
              </label>

              Тип экспорта
              <select name="export_option">
                  <option>INSERT</option>
                  <option>UPDATE</option>
                  <option>REPLACE</option>
              </select>

              {props.fields &&
                <Fragment>
                    <div> Выбрать поля для экспорта:</div>
                    <HtmlSelector data={props.fields} name="fields[]" multiple="multiple" value={props.fields}/>
                </Fragment>
              }
          </div>

      </div>
    )
}