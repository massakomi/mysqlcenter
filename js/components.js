class CharsetSelector extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {

    let opts = [], i = 0
    for (let charset in this.props.charsets) {
      let title = null;
      let info = this.props.charsets[charset]
      if (typeof info == 'object') {
        title = info.Description + ' (default: '+info['Default collation']+')'
      }
      opts.push(<option key={i++} title={title}>{charset}</option>)
    }

    return (
        <select name="charset" defaultValue={this.props.value ? this.props.value : 'utf8'}>{opts}</select>
    );
  }
}

class HtmlSelector extends React.Component {

  constructor(props) {
    super(props);
  }

  onChange = (e) => {
    if (this.props.auto) {
      let value = e.target.options[e.target.selectedIndex].value
      if (value) {
        window.location = value
      }
    }
  }

  render() {

    let opts = [], i = 0
    for (let key in this.props.data) {
      let title = this.props.data[key]
      let value = title
      if (this.props.keyValues) {
        value = key;
      }
      opts.push(<option value={value} key={i++}>{title}</option>)
    }

    return (
        <select onChange={this.props.onChange ? this.props.onChange : this.onChange.bind(this)}
                name={this.props.name}
                multiple={this.props.multiple}
                defaultValue={this.props.value}
                className={this.props.class}>{opts}
        </select>
    );
  }
}


function Messages(props) {

  const MessageText = (item) => {
    let extra = []
    if (item.sql) {
      extra.push(<div key="v1" className="sqlQuery">{item.sql}</div>)
      if (item.rows) {
        extra.push(<div key="v2" style={{color: '#ccc'}}>затронуто рядов: {item.rows}</div>)
      }
      if (item.error) {
        extra.push(<div key="v3" className="mysqlError"><b>Ошибка:</b> {item.error}</div>)
      }
    }
    return (<div style={{color: item.color}}>{item.text}{extra}</div>)
  }

  const CloseMessage = (e) => {
    $(e.target).closest('tr').next().toggle()
  }

  return <div className="messages">{props.messages.map((item, key) =>
    (
      <table className="globalMessage" key={key}>
        <tbody>
        <tr><th>Сообщение <a href="#" className="hiddenSmallLink" style={{color: 'white'}} onClick={CloseMessage.bind(this)}>close</a></th></tr>
        <tr><td>{MessageText(item)}</td></tr>
        </tbody>
      </table>
    )
  )}</div>
}


/**
 * (для tbl_data и tbl_compare) Получить массив заголовков для таблицы данных. Заголовки для таблиц данных
 * формируются особым образом, с переносом.
 *
 * @package data view
 * @param array   Массив SQL объектов-полей (SHOW FIELDS...)
 * @param boolean С возможностью сортировки или без
 * @return array  Массив заголовков
 */
function getTableHeaders(fields, sortEnabled=true, headWrap=false) {
  let headers = [];
  let pk = [];
  let fieldsCount = Object.keys(fields).length;
  Object.keys(fields).forEach(function(k) {
    let v = fields[k]
    let isWrapped = v.Type.match(/(int|enum|float|char)/i) !== null
    if (!headWrap || v.Field.length <= headWrap) {
      isWrapped = false
    }
    if (fieldsCount <= 10) {
      isWrapped = false
    }
    if (new URL(location.href).searchParams.get('fullText') === '1') {
      isWrapped = false
    }
    let u = umaker({s: 'tbl_data', order: v.Field+"-"}, {order: v.Field})
    let link = v.Field;
    if (isWrapped) {
      v.Field = wordwrap(v.Field, headWrap, "\n", true);
      v.Field = v.Field.split("\n")
      let f = []
      let i = 0
      for (let x of v.Field) {
        f.push(<span key={i}>{x}<br /></span>)
        i ++
      }
      v.Field = f
    }
    if (sortEnabled) {
      link = <a href={u} className='sort' title='Сортировать' key={k}>{v.Field}</a>
    }
    headers.push(link)
  })
  return headers;
}
/**
 * (для tbl_data и tbl_compare) Обрабатывает значения полей базы данных перед выводом их в виде таблицы.
 * Обработка заключается в: для текстовых - htmlspecialchars+обрезка, для даты - отображение в поле id=tblDataInfoId
 * для нулевых значений - значение возвращается оформленным курсивом.
 *
 * @package data view
 * @param string Значение
 * @param string Тип поля
 * @return string Обработанное значение
 */
function processRowValue(v, type, textCut) {
  if (v === null) {
    v = <i>NULL</i>
  } else {
    // Тексты
    if (type.match(/(blob|text|char)/i)) {
      v = htmlspecialchars(v)
    }
    if (v.length > textCut) {
      let fullText = new URL(location.href).searchParams.get('fullText');
      if (fullText == '') {
        v = v.substr(0, textCut)
      }
    }
    // дата
    if (type.match(/(int)/i) && v.length == 10 && $.isNumeric(v)) {
      //$e = ' onmouseover="get(\'tblDataInfoId\').innerHTML=\''.date(MS_DATE_FORMAT, $v).'\'" onmouseout="get(\'tblDataInfoId\').innerHTML=\'\'"';
      //$v = '<span className="dateString"'.$e.'>'.$v.'</span>';
    }
  }
  return v
}