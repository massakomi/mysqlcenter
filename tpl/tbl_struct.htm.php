<div id="root"></div>

<script type="text/babel">


  class TableObject extends React.Component {

    constructor(props) {
      super(props);
    }

    render() {

      // нулевой элемент раскидвать по ключ-значение, бред редко нужно
      let tableInfo = this.props.data[0]
      const listItems = []
      for (const key in tableInfo) {
        let title = key in this.props.columns ? this.props.columns[key] : ''
        listItems.push(
            <tr key={key+"index"} title={title}>
              <td>{key}</td>
              <td>{tableInfo[key]}</td>
            </tr>
        )
      }

      return (
          <table className="contentTable">
            <tbody>
            {listItems}
            </tbody>
          </table>
      )
    }
  }


  class TableStruct extends React.Component {

    constructor(props) {
      super(props);
      //this.state = {value: 'wait'};
    }

    // wordwrap($v->Type, 70, '<br />', true)
    wordwrap(s) {
      return s
    }

    deleteField(field, e) {
      e.preventDefault()
      let msquery = 'db='+this.props.db+'&table='+this.props.table+'&s=tbl_struct'
      let urlDelete = msquery + '&field=' + field
      let query = urlDelete + '&id=f-' + encodeURIComponent(field)
      msQuery('deleteField', query)
    }

    render() {
      const listItems = Object.values(this.props.data).map((v, k) => {
        let key = v.Key;
        if (key === 'PRI') {
          key = <img src={this.props.dirImage + "acl.gif"} alt="" border="0" />
        }
        for (let keyObject of this.props.dataKeys) {
          if (keyObject.Column_name === v.Field) {
            let foreignKeys = this.props.foreignKeys[v.Field]
            if (keyObject.Key_name.indexOf('FK') === 0 || (foreignKeys && foreignKeys.CONSTRAINT_TYPE === 'FOREIGN KEY')) {
              key = <span title={foreignKeys && foreignKeys.REFERENCED_TABLE_NAME ? foreignKeys.REFERENCED_TABLE_NAME : keyObject.Table}>FK</span>
            }
          }
        }
        return (
          <tr id={"f-"+v.Field} key={v.Field}>
             <td><input name="field[]" id={"field"+k} type="checkbox" value={v.Field} className="cb" /></td>
             <td>{v.Field}</td>
             <td>{this.wordwrap(v.Type)}</td>
             <td>{v.Null}</td>
             <td>{v.Default}</td>
             <td>{key}</td>
             <td>{v.Extra}</td>
             <td><a href={`?s=tbl_add&table=${this.props.table}&field=`+encodeURIComponent(v.Field)} title="Редактировать ряд"><img src={this.props.dirImage + "edit.gif"} alt="" /></a></td>
             <td><a href="#" onClick={this.deleteField.bind(this, v.Field)} title="Удалить ряд"><img src={this.props.dirImage + "close.png"} alt="" /></a></td>
          </tr>
        )
      });

      return (
          <table className="contentTable">
            <thead>
            <tr>
              <th>&nbsp;</th>
              <th>Поле</th>
              <th>Тип</th>
              <th>NULL</th>
              <th>По умолчанию</th>
              <th>Ключ</th>
              <th>Дополнительно</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
            </tr></thead>
            <tbody>
            {listItems}
            </tbody>
          </table>
      )
    }
  }




  class App extends React.Component {

    constructor(props) {
      super(props);
    }

    chbx_action(opt, event) {
        $('#formTableStructure input[type="checkbox"]').prop('checked', function() {
          return opt == 'check'
        })
    }

    msImageAction = (opt, e) => {
      msImageAction('formTableStructure', opt)
    }

    checkEmpty = (e) => {
      e.preventDefault()
      checkEmpty(e.target, 'fieldsNum')
    }

    selectOnFocus = (e) => {
      get('f3').checked = true
    }

    render() {

      return (
          <div>
            <table>
              <tbody><tr>
                <td valign="top">
                  <form action={this.props.addTableUrl} method="post" name="formTableStructure" id="formTableStructure">
                    <input type="hidden" name="action" value="" />

                    <TableStruct {...this.props} />

                  <div className="chbxAction">
                    <img src={this.props.dirImage + "arrow_ltr.png"} alt="" border="0" align="absmiddle" />
                    <a href="#" onClick={this.chbx_action.bind(this, "check")}>выбрать все</a>  &nbsp;
                    <a href="#" onClick={this.chbx_action.bind(this, "uncheck")}>очистить</a>
                  </div>

                  <div className="imageAction">
                    <u>Выбранные</u>
                    <input type="image" src={this.props.dirImage + "edit.gif"} onClick={msImageAction.bind(this, 'fieldsEdit')} alt="" />
                    <input type="image" src={this.props.dirImage + "close.png"} onClick={msImageAction.bind(this, 'fieldsDelete')} alt="" />
                  </div>
                  </form>

                <a href={this.props.printVersionUrl}>Печатная версия</a>
                <fieldset className="msGeneralForm">
                  <legend>Изменить структуру</legend>
                  <form action={this.props.addTableUrl} method="post" onSubmit={this.checkEmpty}>
                    <input type="hidden" name="action" value="fieldsAdd" />
                    Добавить полей &nbsp; <input name="fieldsNum" type="text" defaultValue="1" size="5" /> &nbsp;
                    <input name="afterOption" type="radio" value="end" defaultChecked id="f1" /> <label htmlFor="f1">в конец </label>
                    <input name="afterOption" type="radio" value="start" id="f2" /> <label htmlFor="f2">в начало</label>
                    <input name="afterOption" type="radio" value="field" id="f3" />  <label htmlFor="f3">после </label>
                    <select name="afterField" onFocus={this.selectOnFocus}>
                      {Object.values(this.props.data).map((table) =>
                          <option key={table.Field}>{table.Field}</option>
                      )}
                    </select>&nbsp;
                    <input type="submit" value="Добавить!" />
                  </form>
                </fieldset>


                </td>
                <td valign="top" style={{padding: '20px 0 0 10px'}}>
                  <strong> Подробности таблицы </strong>
                  <br />
                  <TableObject data={this.props.dataDetails[1]} columns={this.props.dataDetails[0]} />
                </td>
              </tr></tbody>
            </table>

            <strong style={{marginRight: '10px'}}>Информация о ключах</strong>
            <img src={this.props.dirImage + "i-help2.gif"} title="Индексы - это сбалансированные деревья значений указанных в индексе полей и ссылки на физические записи в таблице. Индексы позволяют ускорить работу выполнения запросов в сотни раз и сразу находить нужные данные, вместо того, чтобы последовательно читать всю таблицу." alt="" border="0" align="absmiddle" className="helpimg" /><br />

            <table className="contentTable">
              <thead>
              <tr>
                <th></th>
                <th><span title="Имя таблицы">Таблица</span></th>
                <th><span title="0 - уникальные значения, 1 - не уникальные">Не уникальное</span></th>
                <th><span title="Имя ключа">Ключ</span></th>
                <th><span title="Порядковый номер ключа, начиная с 1">Номер</span></th>
                <th><span title="Имя колонки (поля)">Колонка</span></th>
                <th><span title="Сортировка колонки в ключе. В MySQL, значение ‘A’ (по возрастанию) или NULL (без сортировки)">Сортировка</span></th>
                <th><span title="Приблизительное число уникальных значений в индексе. Это поле обновляется при запуске  ANALYZE TABLE или myisamchk -a. Cardinality расчитывается на основе цифровой статистики, поэтому его значение не обязательно будет точным даже для небольших таблиц. Чем выше cardinality, тем больше шансов, что MySQL будет применять индекс в операциях объединения (JOIN)">Cardinality</span></th>
                <th><span title="Количество индексированных символов, если колонка только частично индексирована, NULL если вся колонка индексирована">Sub_part</span></th>
                <th><span title="Как упакован ключ. NULL если не упакован. Хранение значений в сжатом (упакованном) виде используется, если в индексе присутствуют поля, у которых переменная длина">Packed</span></th>
                <th><span title="YES если колонка может содержать NULL. Если нет, то поле содержит NO после MySQL 5.0.3, и '' в предыдущих версиях">Null</span></th>
                <th><span title="Метод индексирования (BTREE - если длина полей индекса не превышает 10 байт, HASH - хранение значений как хэш кодов. Используется, если индекс составной, его длина больше одной восьмой от размера страницы БД или же больше, чем 256 байт, FULLTEXT, RTREE)">Тип индекса</span></th>
                <th><span title="">Комментарий</span></th>
                <th>Index_comment</th>
                <th>Visible</th>
                <th>Expression</th>
              </tr></thead>
              <tbody>
              {this.props.dataKeys.map((v) => {
                  let href = "?s=tbl_struct&action=deleteKey&key="+v.Key_name+"&field="+v.Column_name
                  return (
                      <tr key={v.Key_name + v.Seq_in_index}>
                        <td><a href={href} onClick={this.checkDelete}><img src={this.props.dirImage + "close.png"} alt="" border="0" /></a></td>
                          {Object.values(v).map((value, key) =>
                              <td key={key + "index"}>{value}</td>
                          )}
                     </tr>
                  )
              })}
              </tbody>
            </table>

            <p><a href={this.props.addKeyUrl}>Добавить ключ</a></p>
            <div className="print_r">{this.props.tableAddStr}</div>
          </div>
      );
    }
  }

  // $umaker->make('s', 'tbl_struct', 'key', $row['Key_name'], 'field', $row['Column_name'], 'action', 'deleteKey')

  let options = <?=json_encode($pageProps)?>;
  ReactDOM.render(
      <App {...options}/>,
      document.getElementById('root')
  );

</script>
