<div id="root"></div>

<script type="text/babel">

  class App extends React.Component {

    constructor(props) {
      super(props);
      this.state = {comment: props.comment, renamename: props.table, messages: []};
    }

    onChangeComment = (e) => {
      this.setState({'comment': e.target.value})
    }

    onChangeRenameName = (e) => {
      this.setState({'renamename': e.target.value})
    }

    tableAction = (action, e) => {
      let opts = {}
      if (e.target.parentNode.tagName === 'FORM') {
        opts = {
          method: 'POST',
          body: new FormData(e.target.parentNode)
        }
      }
      e.preventDefault()
      fetch(this.props.url+'&ajax=1&action='+action, opts)
        .then(response => response.json())
        .then(json => this.setState({messages: json.messages}));
    }

    render() {

      return (
        <React.Fragment>
          <Messages messages={this.state.messages} />
          <table width="100%"  border="0" cellSpacing="0" cellPadding="3">
            <tbody><tr>
              <td>
                <fieldset className="msGeneralForm">
                <legend>Переименовать таблицу в:</legend>
                <form>
                  <input name="newName" type="text" onChange={this.onChangeRenameName} required value={this.state.renamename} />
                  <input type="button" value="Выполнить!" onClick={this.tableAction.bind(this, "tableRename")} disabled={!this.state.renamename} className="submit" />
                </form>
              </fieldset>

                <fieldset className="msGeneralForm">
                  <legend>Переместить таблицы в (база данных.таблица):</legend>
                  <form>
                    <HtmlSelector data={this.props.dbs} name="newDB" auto="false" value={this.props.db} />
                    .
                    <input name="newName" required type="text" defaultValue={this.props.table} />
                    <input type="submit" onClick={this.tableAction.bind(this, "tableMove")} value="Выполнить!" className="submit" />
                  </form>
                </fieldset>

                <fieldset className="msGeneralForm">
                  <legend>Скопировать таблицу в (база данных.таблица):</legend>
                  <form>
                    <HtmlSelector data={this.props.dbs} value={this.props.db} name="newDB" auto="false" />
                    .
                    <input name="newName" type="text" required defaultValue={this.props.table} />
                    <input type="submit" onClick={this.tableAction.bind(this, "tableCopyTo")} value="Выполнить!" className="submit" />
                  </form>
                </fieldset>

                <fieldset className="msGeneralForm">
                  <legend>Изменить кодировку таблицы</legend>
                  <form>
                    <CharsetSelector charsets={this.props.charsets} value={this.props.charset} />
                    <input type="button" onClick={this.tableAction.bind(this, "tableCharset")} value="Выполнить!" />
                  </form>
                </fieldset>

                <fieldset className="msGeneralForm">
                  <legend>Комментарий к таблице</legend>
                  <form>
                    <input name="comment" type="text" size="60" onChange={this.onChangeComment} defaultValue={this.state.comment} />
                    <input type="submit" onClick={this.tableAction.bind(this, "tableComment")} value="Выполнить!" disabled={!this.state.comment} className="submit" />
                  </form>
                </fieldset>
                <fieldset className="msGeneralForm">
                  <legend>Изменить подрядок</legend>
                  <form>
                    <HtmlSelector data={this.props.fields} name="field" />
                    <select name="order"><option value="">По возрастанию</option><option value="DESC">По убыванию</option></select>
                    <input type="submit" onClick={this.tableAction.bind(this, "tableOrder")} value="Выполнить!" className="submit" />
                  </form>
                </fieldset>
                <fieldset className="msGeneralForm">
                  <legend>Опции таблицы</legend>
                  <form>
                    <input type="checkbox" name="checksum" defaultValue={this.props.checksum} /> checksum &nbsp; &nbsp;
                    <input type="checkbox" name="pack_keys" value="1" /> pack_keys
                    <input type="checkbox" name="delay_key_write" value="1" /> delay_key_write &nbsp;
                    <input name="auto_increment" type="text" size="3" defaultValue={this.props.ai} /> auto_increment
                    <input type="submit" onClick={this.tableAction.bind(this, "tableOptions")} value="Выполнить!" className="submit" />
                  </form>
                </fieldset>
              </td>
              <td valign="top">
                <div className="globalMenu">
                  <a onClick={this.tableAction.bind(this, "tableCheck")} href="#">Проверить таблицу</a> <br />
                  <a onClick={this.tableAction.bind(this, "tableAnalize")} href="#">Анализ таблицы</a> <br />
                  <a onClick={this.tableAction.bind(this, "tableRepair")} href="#">Починить таблицу</a> <br />
                  <a onClick={this.tableAction.bind(this, "tableOptimize")} href="#">Оптимизировать таблицу</a>  <br />
                  <a onClick={this.tableAction.bind(this, "tableFlush")} href="#">Сбросить кэш таблицы ("FLUSH")</a> <br />
                </div>
              </td>
            </tr></tbody>
          </table>
        </React.Fragment>
      );
    }
  }

  let options = <?=json_encode($pageProps)?>;
  ReactDOM.render(
      <App {...options} />,
      document.getElementById('root')
  );

</script>