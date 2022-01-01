<div id="root"></div>

<script type="text/babel">

  class App extends React.Component {

    constructor(props) {
      super(props);
    }

    render() {

      return (
          <div>
            <b>1. Выберите таблицу, в которую импортируются данные</b><br />
            <ul>
              {Object.values(this.props.tables).map((table) =>
                  <li key={table}>{table == this.props.table ?
                      <b style={{fontSize: '16px', color: 'red'}}>{table}</b> :
                      <a href={this.props.url.replace('%table%', table)}>{table}</a> }
                  </li>
              )}
            </ul>

            <h3>2. Выберите файл с данными</h3>
            <form name="form1" encType="multipart/form-data" method="post" action="">
              загрузить с компьютера <input type="file" name="file" /> или <br /><br />
              указать путь к файлу <input type="text" name="textfield" /><br /><br />
              <input type="submit" value="Изменить!" />
            </form>

            <h3>3. Настройка параметров импорта</h3>
            <form name="form1" method="post" action="">
              разделитель <input type="text" name="textfield" /><br /><br />
              <input type="submit" value="Импортировать!" />
            </form>
          </div>
      );
    }
  }

  let options = <?=json_encode($pageProps)?>;

  ReactDOM.render(
      <App {...options} />,
      document.getElementById('root')
  );

</script>