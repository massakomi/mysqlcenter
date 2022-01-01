<?php
include DIR_MYSQL . 'tpl/exportOptions.htm.php';
?>


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

    msMultiSelect = (event) => {
      if (event.target.classList.contains('invert')) {
        $('[name="'+this.props.selectMultName+'"] option').prop('selected', function() {
          return !this.selected
        })
      } else {
        $('[name="'+this.props.selectMultName+'"] option').prop('selected', event.target.classList.contains('select'))
      }
    }

    render() {

      return (
      <form action="" method="post" name="formExport">
        <table className="tableExport">
            <tbody><tr>
            <td>
              <select name={this.props.selectMultName} multiple="multiple" className="sel" defaultValue={this.props.optionsSelected}>
                {this.props.optionsData.map((v) =>
                    <option key={v.toString()}>{v}</option>
                )}
              </select><br />
              <a href="#" onClick={this.msMultiSelect} className="hs select">все</a>
              <a href="#" onClick={this.msMultiSelect} className="hs unselect">очистить</a>
              <a href="#" onClick={this.msMultiSelect} className="hs invert">инверт</a>
            </td>
            <td>
                <ExportOptions />

              WHERE условие<br />
              <input name="export_where" type="text" defaultValue={this.props.whereCondition} style={{width:'95%', display:'block', margin:'10px 0'}} />
                <input type="submit" value="Экспортировать!" />
            </td>
            <td> </td>
          </tr></tbody>
        </table>
        <a href={umaker({s: 'exportSp'})}>Специальный экспорт</a>
      </form>
      );
    }
  }

  let options = <?=json_encode($pageProps)?>;
  console.log(options)

  ReactDOM.render(
      <App {...options}/>,
      document.getElementById('root')
  );

</script>
