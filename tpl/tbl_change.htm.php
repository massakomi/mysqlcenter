<div id="root"></div>

<script type="text/babel">

  class MSC_InsertInput extends React.Component {

    render() {

      let i = this.props.i;
      let j = this.props.j;
      let field = this.props.field
      let attr = this.props.attr
      let value = this.props.value
      let diffLength = this.props.diffLength
      let type = this.props.type

      let length = null;
      let a = type.match(/\(([0-9]+)\)/)
      if (a && a[1]) {
        length = parseInt(a[1]);
      }
      if (length == 1) {
        //return '<input name="row['.$i.'][]" type="checkbox" value="1" />';
      }

      if (type.match(/enum/i)) {
        /*
          preg_match_all('~(\'|")(.*)(\'|")~iU', $type, $items);
          if (isset($items[2])) {
            array_unshift($items[2], '');
            foreach ($items[2] as $k => $v) {
              $items[2][$k] = preg_replace('~\s+~i', ' ', $v);
            }
            $value = preg_replace('~\s+~i', ' ', $value);
            $attr = str_replace('onkeyup', 'onchange', $attr);
            return plDrawSelector(
                $items[2],
                ' name="row['.$i.']['.$j.']"'.$attr,
                array_search($value, $items[2]),
                '',
                false
            );
          }
          */
        return 'todo'
      }

      if (type.match(/(text|blob)/i)) {
        let rows = 10
        if (value != null && value.length > 0) {
          rows = Math.round(value.length / 60)
          if (rows < 10) {
            rows = 10
          }
        }
        return <textarea name={`row[${j}][${i}]`} cols="70" rows={rows} defaultValue={value}></textarea>
      }

      let size = 80
      if (diffLength) {
        if (length <= 15) {
          size = length
        } else if (length < 30) {
          size = Math.round(length / 1.2)
        } else {
          size = Math.round(length / 3)
        }
      } else {
        if (length <= 15) {
          size = 15
        } else if (length < 128) {
          size = 50
        } else {
          size = 80
        }
      }
      if (type === 'datetime') {
        size = 30
      }
      if (type === 'timestamp' && value != null) {
        size = 50;
      }

      return <input name={`row[${j}][${i}]`} type="text" size={size} defaultValue={value} className="si" />

      return (
          <React.Fragment>
            test
          </React.Fragment>
      );
    }
  }


  class AddRow extends React.Component {

    wordwrap(s){
      return s
    }

    render() {

      let i = this.props.i;
      let j = this.props.j;
      let name = this.props.name
      let fields = this.props.fields
      let value = this.props.value || ''
      if (value == 'CURRENT_TIMESTAMP') {
        value = ''
      }

      let type = fields[name].Type.replace(',', ', ')
      let nullCheckbox = '&nbsp;';
      let attr = '';
      if (fields[name].Null) {
        let checked = false
        if (value == '' && fields[name].Null == 'YES') {
          checked = true;
        }
        nullCheckbox = <input name={`isNull[${j}][${i}]`} type="checkbox" value="1" defaultChecked={checked} />
      }

      let funcs = ['', 'md5']

      return (
          <tr>
            <td><b className="field">{name}</b><br />{this.wordwrap(type)}</td>
            <td>{nullCheckbox}</td>
            <td><MSC_InsertInput {...this.props} type={type} /></td>
            <td><HtmlSelector data={funcs} name={`func[${j}][${i}]`} /></td>
          </tr>
      )
    }

  }


  class AddRows extends React.Component {

    constructor(props) {
      super(props);
      this.state = {msRowsInsert: props.msRowsInsert};
    }

    addDataRow(inc) {
      this.setState({msRowsInsert: this.state.msRowsInsert + inc})
      refreshActions();
    }

    render() {

      let outerRows = []
      for (let j = 0; j < this.state.msRowsInsert; j++) {
        let tableInnerRows = Object.values(this.props.fields).map((field, i) =>
            <AddRow key={field.Field} name={field.Field} i={i} j={j} fields={this.props.fields} />
        );
        let tableInner = (
            <table style={{marginBottom: '10px'}}>
              <tbody>
              <tr className="editHeader">
                <td>Поле</td>
                <td>Ноль</td>
                <td>Ряд #<span>{j + 1}</span></td>
                <td>Функция</td>
              </tr>
              {tableInnerRows}
              </tbody>
            </table>)
        outerRows.push(<tr key={"row-"+j}><td className="inner">{tableInner}</td></tr>)
      }

      return (
          <form action="" method="post" name="rowsForm" id="rowsForm" className="tableFormEdit">
            <input type="hidden" name="action" value="rowsAdd" />
            <img src={`${this.props.dirImage}nolines_plus.gif`} alt="" border="0" onClick={this.addDataRow.bind(this, 1)} title="Добавить поле" style={{cursor: 'pointer'}} />
            <img src={`${this.props.dirImage}nolines_minus.gif`} alt="" border="0" onClick={this.addDataRow.bind(this, -1)} title="Удалить поле" style={{cursor: 'pointer'}} /><br />
            <table id="tableDataAdd">
                <tbody>
                {outerRows}
                </tbody>
            </table>
            <br />
            после вставки
            <input name="a" type="radio" id="f2" onClick={changeCurrentPage.bind(this)} value="tbl_data" defaultChecked /> <label htmlFor="f2">обзор таблицы</label>
            <input name="a" type="radio" id="f3" onClick={changeCurrentPage.bind(this)} value="tbl_list" /> <label htmlFor="f3">список таблиц</label>
            <input name="a" type="radio" id="f4" onClick={changeCurrentPage.bind(this)} value="tbl_change" /> <label htmlFor="f4">вставить новую запись</label>
            <br /><br />
            <input tabIndex="100" type="submit" value="Вставить данные!" />
          </form>
      );
    }
  }

</script>


<script type="text/babel">


  class EditRows extends React.Component {

    render() {

      let outerRows = []
      let j = 0
      for (let j = 0; j < this.props.tableData.length; j++) {
        let data = this.props.tableData[j]

        let pk = []
        let tableInnerRows = Object.keys(data).map((field, i) => {
          let value = data[field]
          let key = this.props.fields[field].Key;
          if (key.indexOf('PRI') > -1) {
            pk.push(field+'="'+value+'"')
          }
          return <AddRow key={'row'+i} name={field} fields={this.props.fields} value={value} i={i} j={j} />
        });

        let cond = pk.join(' AND ')
        if (pk.length === 0) {
          for (let field of Object.keys(this.props.fields)) {
            let value = data[field]
            if (value == null) {
              continue;
            }
            pk.push(field+'="'+value+'"')
          }
        }
        let hiddenInput = (<input name="cond[]" type="hidden" value={cond} />)

        let tableInner = (
            <React.Fragment key={"row-"+j}>{hiddenInput}
            <table style={{marginBottom: '10px'}}>
              <tbody>
              <tr className="editHeader">
                <td>Поле</td>
                <td>Ноль</td>
                <td>Ряд #<span>{j + 1}</span></td>
                <td>Функция </td>
              </tr>
              {tableInnerRows}
              </tbody>
            </table>
            </React.Fragment>)
        outerRows.push(tableInner)
        j ++
      }

      return (
          <form method="post" action="" name="rowsForm" className="tableFormEdit" id="rowsForm">
            <input type="hidden" name="action" value="rowsEdit" />
                {outerRows}

              <label htmlFor="f3"><input name="option" type="radio" value="save" id="f3" defaultChecked /> сохранить</label>	<br /> или <br />
              <label htmlFor="f4"><input name="option" type="radio" value="insert" id="f4" /> вставить новый ряд</label>	<br />

              <br />
              после вставки
              <input name="a" type="radio" id="f2" onClick={changeCurrentPage.bind(this)} value="tbl_data" defaultChecked /> <label htmlFor="f2">обзор таблицы</label>
              <input name="a" type="radio" id="f3" onClick={changeCurrentPage.bind(this)} value="tbl_list" /> <label htmlFor="f3">список таблиц</label>
              <input name="a" type="radio" id="f4" onClick={changeCurrentPage.bind(this)} value="tbl_change" /> <label htmlFor="f4">вставить новую запись</label>

              <input tabIndex="100" type="submit" value="Вставить данные!" className="submit" />
          </form>
      );
    }
  }

</script>



<script type="text/babel">

  let options = <?=json_encode($pageProps)?>;

  if (options.isAdd) {
    ReactDOM.render(
        <AddRows {...options} />,
        document.getElementById('root')
    );
  } else {
    ReactDOM.render(
        <EditRows {...options} />,
        document.getElementById('root')
    );
  }

  function changeCurrentPage(obj) {
    if (obj.target) {
      obj = obj.target
    }
    get('rowsForm').action = `?s=${obj.value}&table=${options.table}&db=${options.db}`;
  }

  /**
   * Назначает события функций processNullInput / processNull
   */
  function refreshActions() {
    var inputs = get('rowsForm').getElementsByTagName('INPUT')
    for (var i = 0; i < inputs.length; i++) {
      if (inputs[i].type == 'checkbox') {
        var idName = inputs[i].name.substr(6);
        var chbxFieldName = inputs[i].name;
        var textFieldName = 'row' + idName;
        list(get('rowsForm')[textFieldName], 'keyup', function () {
          var chbxFieldName = 'isNull' + this.name.substr(3);
          processNullInput(get('rowsForm')[chbxFieldName], this)
        });
        list(get('rowsForm')[chbxFieldName], 'click', function () {
          var textFieldName = 'row' + this.name.substr(6);
          processNull(this, get('rowsForm')[textFieldName])
        });
      }
    }
  }
  $(document).ready(function () {
    setTimeout(function() {
      refreshActions()
      changeCurrentPage(get('f2'));
    }, 500);
  });

  // Если чекбокс отмечен, то значение текстового поля обнуляется
  function processNull(checkbox, textinput) {
    if (checkbox.checked) {
      textinput.value = '';
    }
  }
  // Если значение текстового поля оказывается пустым, то чекбокс отмечается
  function processNullInput(checkbox, textinput) {
    if (textinput.value != '') {
      checkbox.checked = false;
    }
  }
</script>