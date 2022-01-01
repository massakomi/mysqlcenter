
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
      this.state = {disabled: true};
      //this.updateState = this.updateState.bind(this); // можно забиндить и в форме, но тут только 1 раз
    }

    updateState = (event) => {
      const name = event.target.name;
      this.setState({[name]: event.target.value});
      // тут уже получается есть результаты предыдущего setState, моментально
      this.setState((prevState, prevProps) => {
        let ok = prevState.query || prevState.queryField;
        return { disabled: !ok };
      });
    }

    componentDidMount() {
      //console.log('mount')
      document.querySelector('#queryAll').focus()
      // можно стейты назначить и тут, а не в конструкторе, если они все равно приходят
      this.setState({'query': this.props.query})
      this.setState({'queryField': this.props.queryField})
      //console.log('will mount')
    }

    msMultiSelect(event) {
      if (event.target.classList.contains('invert')) {
        $('[name="table[]"] option').prop('selected', function() {
          return !this.selected
        })
      } else {
        $('[name="table[]"] option').prop('selected', event.target.classList.contains('select'))
      }
    }

    render() {
      return (
          <form action="/?s=search" method="post" name="formSearch">
            <table className="tableExport">
                <tbody><tr>
                <td valign="top">
                  <select name="table[]" multiple className="sel" defaultValue={this.props.tables}>
                    {Object.values(this.props.tables).map((table) =>
                        <option key={table.toString()}>{table}</option>
                    )}
                  </select>   <br />
                  <a href="#" onClick={this.msMultiSelect} className="hs select">все</a> &nbsp;
                  <a href="#" onClick={this.msMultiSelect} className="hs unselect">очистить</a> &nbsp;
                  <a href="#" onClick={this.msMultiSelect} className="hs invert">инверт</a>
                </td>
                <td valign="top">
                  искать по всем полям    <br />
                  <input name="query" id="queryAll" type="text" size="50" onChange={this.updateState} value={this.state.query} /><br />
                  искать имя поля    <br />
                  <input name="queryField" type="text" size="50" onChange={this.updateState} value={this.state.queryField} /><br /> <br />
                  <input type="submit" value="Искать!" className="submit" disabled={this.state.disabled} />
                </td>
              </tr></tbody>
            </table>
          </form>
      );
    }
  }

  let options = {
    'query': "<?php echo POST('query')?>",
    'queryField': "<?php echo POST('queryField')?>",
    'tables': <?=json_encode($listTables)?>
  }

  ReactDOM.render(
      <App {...options} />,
      document.getElementById('root')
  );

</script>
