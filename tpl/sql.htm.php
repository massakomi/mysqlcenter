<div id="root"></div>

<script type="text/babel">

  class App extends React.Component {

    constructor(props) {
      super(props);
      this.state = {value: 'wait'};
    }

    render() {

      const opts = Object.values(this.props.charsets).map((charset) =>
          <option key={charset.toString()}>
            {charset}
          </option>
      );

      return (
          <form method="post" encType="multipart/form-data" name="sqlQueryForm" id="sqlQueryForm" className="tableFormEdit">
            <textarea name="sql" rows="20" id="sqlContent" wrap="off">{this.props.sql}</textarea>
            <input type="submit" value="Отправить запрос!" className="submit" />
            <fieldset className="msGeneralForm">
              <legend>Запрос из файл</legend>
              <input type="hidden" name="MAX_FILE_SIZE" value={this.props.maxUploadSize} />
              <input type="file" name="sqlFile" /> <br />
              Сжатие:
              <input name="compress" type="radio" value="auto" defaultChecked="checked" />  Автодетект
              <input name="compress" type="radio" value="" />     Нет
              <input name="compress" type="radio" value="gzip" />     gzip
              <input name="compress" type="radio" value="zip" />     zip
              <input name="compress" type="radio" value="excel" />  excel
              <input name="compress" type="radio" value="csv" />  csv
              <br />
              Кодировка файла: <select name="sqlFileCharset" defaultValue="utf8">{opts}</select><br />
              (Максимальный размер: {this.props.maxSize} Mb)
            </fieldset>
          </form>
      );
    }
  }

  let options = <?=json_encode($pageProps)?>;

  ReactDOM.render(
      <App {...options} />,
      document.getElementById('root')
  );

</script>




