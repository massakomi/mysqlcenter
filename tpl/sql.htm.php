
<div id="root"></div>

<!-- Load React. -->
<!-- Note: when deploying, replace "development.js" with "production.min.js". -->
<script src="https://unpkg.com/react@16/umd/react.development.js" crossorigin></script>
<script src="https://unpkg.com/react-dom@16/umd/react-dom.development.js" crossorigin></script>
<script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>


<script type="text/babel">

  class App extends React.Component {

    constructor(props) {
      super(props);
      this.state = {value: 'wait'};
    }

    render() {

      const opts = Object.values(this.props.options.charsets).map((charset) =>
          <option key={charset.toString()}>
            {charset}
          </option>
      );

      return (
          <form method="post" encType="multipart/form-data" name="sqlQueryForm" id="sqlQueryForm" className="tableFormEdit">
            <textarea name="sql" rows="20" id="sqlContent" wrap="off"><?php echo POST('sql')?></textarea>
            <input type="submit" value="Отправить запрос!" className="submit" />
            <fieldset className="msGeneralForm">
              <legend>Запрос из файл</legend>
              <input type="hidden" name="MAX_FILE_SIZE" value={this.props.options.maxUploadSize} />
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
              (Максимальный размер: {this.props.options.maxSize} Mb)
            </fieldset>
          </form>
      );
    }
  }

  let options = {
    'maxUploadSize': '<?=MAX_UPLOAD_SIZE?>',
    'maxSize': '<?php echo round(MAX_UPLOAD_SIZE / (1024*1024), 2)?>',
    'charsets': <?=json_encode($mysql_charsets)?>
  }
  ReactDOM.render(
      <App options={options} />,
      document.getElementById('root')
  );

</script>




