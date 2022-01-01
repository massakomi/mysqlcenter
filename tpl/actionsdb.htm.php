<div id="root"></div>

<script type="text/babel">

  class TableObject extends React.Component {

    constructor(props) {
      super(props);
    }

    render() {

      if (typeof this.props.data != 'object') {
        return (
            <div style="color:red">Это не объект!</div>
        );
      }

      let rows = []
      let index = 0;
      for (let i in this.props.data) {
        rows.push(
            <tr key={index ++}>
              <td>{i}</td>
              <td>{this.props.data[i]}</td>
            </tr>
        )
      }

      return (
          <table className={this.props.className}>
            <tbody>
            {rows}
            </tbody>
          </table>
      );
    }
  }

  function FieldSet(props) {
    return (
      <fieldset className="msGeneralForm">
        <legend>{props.title}</legend>
        <form action={props.url + "&action=" + props.action} method="post" name={props.action}>
        {props.children}
        <input type="submit" value="Выполнить!" style={{marginLeft: '5px'}} />
      </form>
      </fieldset>
    )
  }


  class App extends React.Component {

    fullinfo = () => {
      fetch(this.props.url+'&ajax=1&action=fullinfo')
        .then(response => response.json())
        .then(json => this.setState({dbInfo: json.page.dbInfo}))
    }

    constructor(props) {
      super(props);
      this.state = {dbInfo: props.dbInfo};
    }

    render() {

      //<input name="auto" type="checkbox" value="1" checked> Добавить значение AUTO_INCREMENT<br>
      //<input name="limit" type="checkbox" value="1"> Добавить ограничения<br>

      return (
          <div>
            <FieldSet title="Переименовать базу данных в:" action="dbRename" {...this.props}>
                <input name="newName" type="text" required defaultValue={this.props.db} />
            </FieldSet>
            <FieldSet title="Копировать базу данных в:" action="dbCopy" {...this.props}>
                <input name="newName" required type="text" defaultValue={this.props.db+"_copy"} /><br />
                <input name="option" type="radio" value="struct" /> Только структуру  <br />
                <input name="option" type="radio" value="all" defaultChecked /> Структура и данные  <br />
                <input name="option" type="radio" value="data" /> Только данные  <br />
                <input name="switch" type="checkbox" value="1" /> Перейти к скопированной БД <br /><br />
            </FieldSet>
            <FieldSet title="Изменить кодировку базы данных:" action="dbCharset" {...this.props}>
                <CharsetSelector charsets={this.props.charsets} />
            </FieldSet>
            <fieldset className="msGeneralForm">
              <legend>Информация о базе данных</legend>
              <TableObject data={this.state.dbInfo} className="contentTable" />
              <br />
              <a href="#" onClick={this.fullinfo}>Показать полную информацию</a>
            </fieldset>
          </div>
      );
    }
  }

  let options = <?=json_encode($pageProps)?>;
  ReactDOM.render(
      <App {...options}/>,
      document.getElementById('root')
  );

</script>
