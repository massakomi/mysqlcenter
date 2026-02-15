import {Fragment, useId, useMemo} from "react";
import React from 'react';
import {sqlFormSubmit} from "../functions";

export default function Sql (props) {

    const noId = useId();
    const gzipId = useId();
    const zipId = useId();

    const charsetOptions = useMemo(() => 
        props.charsets.map((charset) => (
            <option key={charset.toString()} value={charset.toLowerCase()}>{charset.toLowerCase()}</option>
        )), 
        [props.charsets]
    );

    return (
        <form method="post" encType="multipart/form-data" name="sqlQueryForm" id="sqlQueryForm" className="tableFormEdit" onSubmit={(e) => sqlFormSubmit(e)}>
            <textarea name="sql" rows="20" id="sqlContent" style={{ whiteSpace: 'pre', overflowX: 'auto' }}>{props.sql}</textarea>
            <input type="submit" value="Отправить запрос!" className="submit mt-10"/>
            <fieldset className="msGeneralForm">
                <legend>Запрос из файла</legend>
                <input type="hidden" name="MAX_FILE_SIZE" value={props.maxUploadSize}/>
                <div className="flex baseline">
                    <input type="file" name="sqlFile" className="mr-20" />
                    <div class="flex baseline mr-20">
                        <span>Сжатие:</span>
                        <div>
                            <input name="compress" type="radio" value="auto" defaultChecked/><label>Автодетект</label>
                            <input name="compress" type="radio" value="" id={noId}/><label htmlFor={noId}>Нет</label>
                            <input name="compress" type="radio" value="gzip" id={gzipId}/><label htmlFor={gzipId}>gzip</label>
                            <input name="compress" type="radio" value="zip" id={zipId}/><label htmlFor={zipId}>zip</label>
                            <input name="compress" type="radio" value="excel"/><label>excel</label>
                            <input name="compress" type="radio" value="csv"/><label>csv</label>
                        </div>
                    </div>
                    <div>
                        Кодировка файла: <select name="sqlFileCharset" defaultValue="utf-8">{charsetOptions}</select>
                    </div>
                </div>
                <p>(Максимальный размер: {props.maxSize} Mb)</p>
            </fieldset>
        </form>
    );
}
