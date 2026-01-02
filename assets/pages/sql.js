import {Fragment, useId} from "react";
import React from 'react';
import {sqlFormSubmit} from "../functions";

export default function Sql (props) {

    const noId = useId();
    const gzipId = useId();
    const zipId = useId();

    const opts = Object.values(props.charsets).map((charset) =>
      <option key={charset.toString()}>
          {charset.toLowerCase()}
      </option>
    );

    return (
      <Fragment>
          <form method="post" encType="multipart/form-data" name="sqlQueryForm" id="sqlQueryForm" className="tableFormEdit" onSubmit={sqlFormSubmit.bind(this)}>
              <textarea name="sql" rows="20" id="sqlContent" wrap="off">{props.sql}</textarea>
              <input type="submit" value="Отправить запрос!" className="submit mt-10"/>
              <fieldset className="msGeneralForm">
                  <legend>Запрос из файл</legend>
                  <input type="hidden" name="MAX_FILE_SIZE" value={props.maxUploadSize}/>


                  <div className="flex baseline">
                      <input type="file" name="sqlFile" style={{'margin-right': '-40px'}} />

                      <div>
                          Сжатие:
                          <input name="compress" type="radio" value="auto" defaultChecked="checked"/> Автодетект
                          <input name="compress" type="radio" value="" id={noId}/> <label htmlFor={noId}>Нет</label>
                          <input name="compress" type="radio" value="gzip" id={gzipId}/> <label htmlFor={gzipId}>gzip</label>
                          <input name="compress" type="radio" value="zip" id={zipId}/> <label htmlFor={zipId}>zip</label>
                          <input name="compress" type="radio" value="excel"/> excel
                          <input name="compress" type="radio" value="csv"/> csv
                      </div>


                      <div>
                          Кодировка файла: <select name="sqlFileCharset" defaultValue="utf-8">{opts}</select>
                      </div>

                  </div>


                  (Максимальный размер: {props.maxSize} Mb)

              </fieldset>
          </form>
      </Fragment>
    );
}
