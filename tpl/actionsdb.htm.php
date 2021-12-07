<div id="root"></div>

<!-- Load React. -->
<!-- Note: when deploying, replace "development.js" with "production.min.js". -->
<script src="https://unpkg.com/react@16/umd/react.development.js" crossorigin></script>
<script src="https://unpkg.com/react-dom@16/umd/react-dom.development.js" crossorigin></script>
<script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>
<script type="text/babel" src="/js/components.js"></script>


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


  class App extends React.Component {

    constructor(props) {
      super(props);
      //this.state = {option: '<?=POST('option', 'all')?>'};
      this.state = {option: 'all'};
      this.onOptionChange = this.onOptionChange.bind(this)
    }

    onOptionChange(e) {
      this.setState({
        option: e.currentTarget.value
      });
    }

    render() {

      //<input name="auto" type="checkbox" value="1" checked> Добавить значение AUTO_INCREMENT<br>
      //<input name="limit" type="checkbox" value="1"> Добавить ограничения<br>

      return (
          <div>
            <fieldset className="msGeneralForm">
              <legend>Переименовать базу данных в:</legend>
              <form action={this.props.options.url + "&action=dbRename"} method="post" name="renameDBForm">
                <input name="newName" type="text" required defaultValue="<?php echo GET('db')?>" />
                <input type="submit" value="Выполнить!" />
              </form>
            </fieldset>
            <fieldset className="msGeneralForm">
              <legend>Копировать базу данных в:</legend>
              <form action={this.props.options.url + "&action=dbCopy"} method="post" onSubmit={this.checkEmpty} name="copyDBForm">
                <input name="newName" required type="text" defaultValue="<?php echo GET('db')?>_copy" /><br />
                <input name="option" type="radio" value="struct" onChange={this.onOptionChange} checked={this.state.option === 'struct'} /> Только структуру  <br />
                <input name="option" type="radio" value="all" onChange={this.onOptionChange} checked={this.state.option === 'all'} /> Структура и данные  <br />
                <input name="option" type="radio" value="data" onChange={this.onOptionChange} checked={this.state.option === 'data'} /> Только данные  <br />
                <input name="switch" type="checkbox" value="1" /> Перейти к скопированной БД <br /><br />
                <input type="submit" value="Выполнить!" />
              </form>
            </fieldset>
            <fieldset className="msGeneralForm">
              <legend>Изменить кодировку базы данных:</legend>
              <form action={this.props.options.url + "&action=dbCharset"} method="post" name="charsetDBForm">
                <CharsetSelector charsets={this.props.options.charsets} />
                <input type="submit" value="Выполнить!" />
              </form>
            </fieldset>
            <fieldset className="msGeneralForm">
              <legend>Информация о базе данных</legend>
              <TableObject data={this.props.options.dbInfo} className="contentTable" />
              <br />
              <a href={this.props.options.url + "&action=fullinfo"}>Показать полную информацию</a>
            </fieldset>
          </div>
      );
    }
  }

  let options = {
    'url': '<?php echo $DQuery?>',
    'dbInfo': <?=json_encode($dbInfo)?>,
    'charsets': <?=json_encode($charsetList)?>
  }
  ReactDOM.render(
      <App options={options}/>,
      document.getElementById('root')
  );

</script>
