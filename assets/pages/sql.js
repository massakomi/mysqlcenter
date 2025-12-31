import {Fragment} from "react";
import React from 'react';
import {sqlFormSubmit} from "../functions";

export function Sql (props) {

    const opts = Object.values(props.charsets).map((charset) =>
      <option key={charset.toString()}>
          {charset}
      </option>
    );

    return (
      <Fragment>
          <form method="post" encType="multipart/form-data" name="sqlQueryForm" id="sqlQueryForm"
                className="tableFormEdit" onSubmit={sqlFormSubmit.bind(this)}>
              <textarea name="sql" rows="20" id="sqlContent" wrap="off">{props.sql}</textarea>
              <input type="submit" value="Отправить запрос!" className="submit mt-10"/>
              <fieldset className="msGeneralForm">
                  <legend>Запрос из файл</legend>
                  <input type="hidden" name="MAX_FILE_SIZE" value={props.maxUploadSize}/>
                  <input type="file" name="sqlFile"/> <br/>
                  Сжатие:
                  <input name="compress" type="radio" value="auto" defaultChecked="checked"/> Автодетект
                  <input name="compress" type="radio" value=""/> Нет
                  <input name="compress" type="radio" value="gzip"/> gzip
                  <input name="compress" type="radio" value="zip"/> zip
                  <input name="compress" type="radio" value="excel"/> excel
                  <input name="compress" type="radio" value="csv"/> csv
                  <br/>
                  Кодировка файла: <select name="sqlFileCharset" defaultValue="utf8">{opts}</select><br/>
                  (Максимальный размер: {props.maxSize} Mb)
              </fieldset>
          </form>
      </Fragment>
    );
}
