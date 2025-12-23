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
      if (item.error !== '' && item.error != null) {
        extra.push(<div key="v3" className="mysqlError"><b>Ошибка:</b> {item.error}</div>)
      }
    }
    return (<div style={{color: item.color}}>{item.text}{extra}</div>)
  }

  const CloseMessage = (e) => {
      e.target.closest('tr').nextElementSibling.toggleAttribute('hidden')
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


class ExportOptions extends React.Component {

    constructor(props) {
        super(props);
        this.state = {value: 'wait'};
    }

    image(src) {
        return this.props.dirImage + src;
    }

    render() {
        return (
          <div className="options">
              <div>
                  <label htmlFor="f1"><input type="checkbox" value="1" name="export_struct" id="f1"
                                             defaultChecked={this.props.structChecked}/> Структура</label>

                  <label htmlFor="f2" title="Укажите эту опцию, если вы хотите заменить таблицу (команда DROP TABLE)">
                      <input type="checkbox" value="1" className="l2" name="addDrop" id="f2"/>
                      Добавить удаление таблицы
                  </label>

                  <label htmlFor="f3"
                         title="Будут преобразованы команды: CREATE TABLE IF NOT EXISTS... и при удалении таблиц DROP TABLE IF EXISTS ...">
                      <input type="checkbox" value="1" className="l2" name="addIfNot" id="f3"/>
                      Добавить IF NOT EXISTS
                  </label>

                  <label htmlFor="f4" title="К каждой таблице будет добавлено AUTO_INCREMENT=текущее значение">
                      <input type="checkbox" value="1" className="l2" name="addAuto" id="f4"/>
                      Добавить значение AUTO_INCREMENT
                  </label>

                  <label htmlFor="f5" title="Оставьте эту опцию, чтобы быть уверенным, что всё пройдет гладко">
                      <input type="checkbox" value="1" className="l2" name="addKav" id="f5" defaultChecked/>
                      Обратные `кавычки` в названиях таблиц и полей
                  </label>

                  <label htmlFor="f12" title="Шапка к дампу с информацией о версиях ПО, а также заголовки таблиц">
                      <input type="checkbox" value="1" name="addComment" id="f12" defaultChecked/>
                      Добавлять комментарии
                  </label>

                  <label htmlFor="f13"><input name="export_to" id="f13" type="radio" value="1"/> в архив</label>
                  <label htmlFor="f14"><input name="export_to" id="f14" type="radio" value="2" defaultChecked/> в текст</label>
              </div>
              <div>
                  <label htmlFor="f6"><input type="checkbox" value="1" name="export_data" id="f6" defaultChecked/>Данные</label>

                  <label htmlFor="f7">
                      <input type="checkbox" value="1" className="l2" name="insFull" id="f7" defaultChecked />
                      Указать все поля <img src={this.image("i-help2.gif")} title="В запросе будут перечислены все поля INSERT INTO table (fields...) VALUES (...), иначе перечисление полей пропускается. Используейте эту опцию, если вы не уверены, что порядок полей сохранится." className="helpimg" alt="" />
                  </label>

                  <label htmlFor="f8">
                      <input type="checkbox" value="1" className="l2" name="insExpand" id="f8" />
                      Одним запросом <img src={this.image("i-help2.gif")} title="Все вставки будут осуществлены одним запросом вида INSERT INTO table VALUES (set1..), (set2...), (set3...) etc" className="helpimg" alt="" />
                  </label>

                  <label htmlFor="f9" >
                      <input type="checkbox" value="1" className="l2" name="insZapazd" id="f9" />
                      DELAYED <img src={this.image("i-help2.gif")} title="DELAYED. Сервер сначала отправит запрос в буфер и если таблица используется, то вставку рядов приостановится. Когда таблица освободится, сервер начнёт выполнять запрос и вставлять строки, периодически проверяя, появились ли новые запросы к таблице. Если да, то вставка рядов будет снова приостановлена до того момента, как таблица снова освободится.
--- Эта опция полезна, когда немедленное обновление таблицы не требуется (например, при логах), а также если осуществляется множество запросов на вставку. Это даёт существенный прирост производительности при вставках и не задерживает обычную выборку. В то же время такие запросы медленнее обычных и вы должны быть уверенными, что они вам нужны." className="helpimg" alt="" />
                  </label>

                  <label htmlFor="f10">
                      <input type="checkbox" value="1" className="l2" name="insIgnor" id="f10" />
                      IGNORE <img src={this.image("i-help2.gif")} title="IGNORE. Ошибки, которые происходят при выполнении INSERT запроса игнорируются, то есть статус сообщения об ошибке меняется с ERROR на WARNING. С IGNORE, неправильные значения исправляются до ближайших валидных значений и вставляются, warning`и появляются, но выражение выполняется. Кроме этого в выражениях вида INSERT IGNORE INTO sdf (a,b) VALUES (6,6), (2,2), (7,7) будут вставлены все значения за исключением дублирующих. При отсутствии опции IGNORE будут вставлены все значения ДО дублирующих и выскочит ошибка." alt="" className="helpimg" />
                  </label>

                  Тип экспорта
                  <select name="export_option">
                      <option>INSERT</option>
                      <option>UPDATE</option>
                      <option title="REPLACE работает точно так же, как INSERT, за исключением тех случаев, когда старая строка в таблице имеет те же значения, что и новая строка для полей с индексами PRIMARY KEY или UNIQUE. В этом случае старый ряд будет удалён перед вставкой нового ряда. REPLACE это собственное расширение MySQL. Он либо вставляет, либо удаляет и вставляет. Заметьте, что если таблица не имеет ключей PRIMARY KEY либо UNIQUE, то использование REPLACE не даст ничего. В этом случае он становится аналогичным INSERT">REPLACE</option>
                  </select>

                  {this.props.fields &&
                    <React.Fragment>
                        <div> Выбрать поля для экспорта:</div>
                        <HtmlSelector data={this.props.fields} name="fields[]" multiple="multiple" value={this.props.fields} />
                    </React.Fragment>
                  }
              </div>

          </div>
        );
    }
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
      v.Field = v.Field.split("\n")
      let f = []
      let i = 0
      for (let x of v.Field) {
        f.push(<span key={i}>{x}</span>)
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