<style type="text/css">
    [name="search_for"], [name="replace_in"], [name="where"] {width: 95%}
    #search_table select, .mr10 {margin-right: 10px}
</style>

<div id="search_table"></div>

<script type="text/babel" src="/js/components.js"></script>

<script type="text/babel">

  class SearchTable extends React.Component {

    constructor(props) {
      super(props);
      this.state = {value: 'wait'};
    }

    sendGet = (e) => {
      window.location = e.target.action + '&where=' + e.target.elements[0].value;
    }

    onFunctionChange = (e) => {
      document.querySelector('#where').value += e.target.options[e.target.selectedIndex].text + ' '
    }

    render() {

      let fields = <?=json_encode($fields)?>;
      fields.unshift('[поля]')
      let opers = <?=json_encode(['[операнды]', ' = ', ' != ', ' < ', ' > ', 'IS NULL',
          ' LIKE "%%" ', ' LIKE "%" ', ' LIKE "" ', ' NOT LIKE "" ',
          ' REGEXP "^fo" '])?>;
      let funcs = <?=json_encode(['[функции]', 'UPPER()', 'LOWER()', 'TRIM()', 'SUBSTRING()', 'REPLACE()', 'REPEAT()'])?>

      return (
          <div>
            <fieldset className="msGeneralForm">
            <legend>Добавить к условию WHERE</legend>
            <form action="<?php echo $umaker->make('s', 'tbl_data')?>" method="get" onSubmit={this.sendGet}>
              <input name="where" type="text" id="where" />
              <input type="submit" value="Выполнить!" className="submit mr10" style={{display: 'inline'}} />
              <span className="mr10">вставить</span>

              <HtmlSelector data={fields} onChange={this.onFunctionChange} />
              <HtmlSelector data={opers} onChange={this.onFunctionChange} />
              <HtmlSelector data={funcs} onChange={this.onFunctionChange} />

            </form>
            </fieldset>

            <fieldset className="msGeneralForm">
              <legend>Найти и заменить</legend>
              <form action="" method="post">
                <table width="100%"  border="0" cellSpacing="0" cellPadding="3">
                  <tbody>
                  <tr>
                    <td width="100">Найти</td>
                    <td><input name="search_for" type="text" defaultValue="<?php echo POST('search_for')?>" /></td>
                  </tr>
                  <tr>
                    <td width="100">Заменить</td>
                    <td><input name="replace_in" type="text" defaultValue="<?php echo POST('replace_in')?>" /></td>
                  </tr>
                  <tr>
                    <td width="100">Поле</td>
                    <td><HtmlSelector data={this.props.fields} name="field" /></td>
                  </tr>
                </tbody>
                </table>
                <input type="submit" value="Выполнить" className="submit" />
              </form>
            </fieldset>
          </div>
      );
    }
  }

  let options = {
    'fields': <?=json_encode($fields)?>
  }
  ReactDOM.render(
      <SearchTable {...options} />,
      document.getElementById('search_table')
  );

</script>