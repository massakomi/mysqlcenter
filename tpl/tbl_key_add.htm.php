<div id="root"></div>

<script type="text/babel">

  class App extends React.Component {

    addRow(tableId, param) {
      addRow(tableId, param)
    }

    removeRow(tableId) {
      removeRow(tableId)
    }

    render() {

      let types = ['PRIMARY KEY','INDEX','UNIQUE','FULLTEXT']

      return (
          <form method="post" action="" className="tableFormEdit">

            имя индекса:
            <input type="text" required name="keyName" defaultValue={this.props.keyName} /><br /><br />
              тип индекса:
              <select name="keyType" value={this.props.keyType}>
                {types.map((t) =>
                    <option key={t}>{t}</option>
                )}
              </select>

              <br /><br />

              <img src={`${this.props.dirImage}nolines_plus.gif`} alt="" border="0" onClick={this.addRow.bind(this, 'tableFormEdit', 'last')} title="Добавить поле" style={{cursor: 'pointer'}}  />
              <img src={`${this.props.dirImage}nolines_minus.gif`} alt="" border="0" onClick={this.removeRow.bind(this, 'tableFormEdit')}  title="Удалить поле" style={{cursor: 'pointer'}} /><br />

              <table id="tableFormEdit">
                  <tbody>
                <tr>
                  <th>Поле</th>
                  <th>Размер</th>
                </tr>
                <tr>
                  <td>
                    <select name="field[]">
                      {Object.keys(this.props.fieldRows).map((field) =>
                          <option value={field} key={field}>{this.props.fieldRows[field]}</option>
                      )}
                    </select>
                  </td>
                  <td><input type="text" name="length[]" size="10" /></td>
                </tr></tbody>
              </table>

              <input type="submit" value="Выполнить" className="submit" />
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
