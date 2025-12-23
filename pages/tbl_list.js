

class Selector extends React.Component {

    render() {
        let options = []

        if (this.props.data) {
            options = this.props.data.map((value, i) =>
              <option key={i} value={i}>{value}</option>
            );
        } else {
            for (let i = this.props.from; i <= this.props.to; i ++) {
                options.push(
                  <option key={i}>{i}</option>
                )
            }
        }

        return (
          <select name={this.props.name} defaultValue={this.props.value}>
              {options}
          </select>
        );
    }
}

class DateSelector extends React.Component {

    constructor(props) {
        super();
        let date = new Date()
        this.state = {day: date.getDate(), month: date.getMonth(), year: date.getFullYear()}
    }

    render() {

        Date.prototype.daysInMonth = function() {
            return 32 - new Date(this.getFullYear(), this.getMonth(), 32).getDate();
        };

        let date = new Date(this.state.year, this.state.month, this.state.day)

        let months = ['января', 'февраля', 'марта', 'апреля', 'май', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря']

        return (
          <React.Fragment>
              <Selector name="ds_day" from="1" to={date.daysInMonth()} value={this.state.day} />
              <Selector name="ds_month" data={months} value={this.state.month} />
              <Selector name="ds_year" from="2000" to={date.getFullYear()} value={this.state.year} />
              <Selector name="ds_hour" from="0" to="23" /> :
              <Selector name="ds_minut" from="0" to="59" /> :
              <Selector name="ds_second" from="0" to="59" />
          </React.Fragment>
        );
    }
}


class TableList extends React.Component {

    image(src) {
        return <img src={this.props.dirImage + src} alt="" />
    }

    async tableDelete(table, e) {
        e.preventDefault()
        let tr = e.target.closest('tr')
        let query = `db=${this.props.db}&table=${table}`;
        await msQuery('tableDelete', query, () => {
            tr.remove()
        })
    }

    renameTable(tableOld, e) {
        let label = e.target
        let newName = prompt('Новое имя', tableOld)
        if (newName) {
            let q = `db=${this.props.db}&s=tbl_list&table=${tableOld}&newName=${newName}`;
            msQuery('tableRename', q, () => {
                label.innerHTML = newName
            });
        }
    }

    printSize(size) {
        // меньше 1мб не нужно выводить
        if (size < 1024*1024) {
            return ''
        }
        let color = 'red'
        // до 10мб слабже
        if (size < 1024*1024*20) {
            color = '#aaa'
        } else if (size < 1024*1024*100) {
            color = 'black'
        }
        let formattedSize = formatSize(size)
        return <span title={size} style={{'color': color}}>{formattedSize}</span>
    }

    renderRow(table, key) {
        // Увеличение счётчика видимых таблиц
        let sumTable = key + 1
        // Форматирование даты
        let updateTime = null;
        if (table.Update_time) {
            var now = new Date(table.Update_time);
            updateTime = now.toLocaleString() // TODO сделать date2rusString
        }
        /*if ($o->Update_time > 0) {
          $updateTime = strtotime($o->Update_time);
          $updateTime = date2rusString(MS_DATE_FORMAT, $updateTime);
          if (strpos($updateTime, 'дня') !== false || strpos($updateTime, 'ера') !== false) {
            $updateTime = "<b>$updateTime</b>";
          }
        }*/
        // Форматирование названия таблицы
        let valueName = table.Name
        if (table.Rows === '0') {
            valueName = <span style={{color: '#aaa'}}> {valueName}</span>
        }
        // Определение размера таблицы
        let size = parseInt(table.Data_length) + parseInt(table.Index_length);
        this.sumSize += parseInt(size);
        this.sumRows += parseInt(table.Rows);
        // Сборка значения рядов
        let msquery = `db=${this.props.db}&table=${table.Name}`;
        let idRow = "row" + sumTable;
        let idChbx = 'table_' + table.Name
        let engine = table.Engine === 'MyISAM' ? <span style={{color: '#ccc'}}>MyISAM</span> : table.Engine;

        return (
          <tr key={table.Name} id={idRow}>
              <td><input name="table[]" type="checkbox" value={table.Name} id={idChbx} className="cb" /></td>
              <td className="tbl"><label htmlFor={idChbx} onDoubleClick={this.renameTable.bind(this, table.Name)}>{valueName}</label></td>
              <td><a href={`/?db=${this.props.db}&table=${table.Name}&s=tbl_data`} title="Обзор таблицы">{this.image("actions.gif")}</a></td>
              <td><a href={`/?db=${this.props.db}&table=${table.Name}&s=tbl_struct`} title="Структура таблицы">{this.image("generate.png")}</a></td>
              <td>
                  <a href="#" onClick={msQuery.bind(this, 'tableTruncate', msquery)} title="Очистить таблицу">{this.image("delete.gif")}</a>
              </td>
              <td>
                  <a href="#" onClick={this.tableDelete.bind(this, table.Name)} title="Удалить таблицу">{this.image("close.png")}</a>
              </td>
              <td className="rig">{table.Rows}</td>
              <td className="rig">{this.printSize(size)}</td>
              <td>{updateTime}</td>
              <td className="num">{table.Auto_increment}</td>
              <td><span>{engine}</span></td>
              <td className="rig"><span title={table.Collation} style={{color: '#aaa'}}>{table.Collation.substr(0, table.Collation.indexOf("_"))}</span></td>
          </tr>
        )
    }

    render() {

        let tables = Object.values(this.props.tables)
        this.sumSize = 0;
        this.sumRows = 0;
        let trs = tables.map((table, key) => this.renderRow(table, key))

        return (
          <table className="contentTable interlaced">
              <thead>
              <tr>
                  <th></th>
                  <th>Таблица</th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th>Рядов</th>
                  <th>Размер</th>
                  <th>Дата обновления</th>
                  <th>Ai</th>
                  <th>Engine</th>
                  <th>Cp</th>
              </tr></thead>
              <tbody>
              {trs}
              <tr>
                  <td></td>
                  <td className="tbl">{tables.length} таблиц</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td className="rig">{Number(this.sumRows).toFixed(0)}</td>
                  <td className="rig">{this.printSize(this.sumSize)}</td>
                  <td></td>
                  <td className="num"></td>
                  <td></td>
                  <td className="rig"></td>
              </tr></tbody>
          </table>
        );
    }
}


class Tbl_list extends React.Component {

    constructor(props) {
        super(props);
        this.state = {value: 'wait', tables: props.tables};
    }

    msImageAction = (opt, url, e) => {
        if (opt === 'auto') {
            opt = this.target.options[this.target.selectedIndex].value
        }
        msImageAction('formTableList', opt, url)
    }

    chbx_action = (opt, e) => {
        e.preventDefault()
        chbx_action('formTableList', opt, 'table[]')
    }

    componentDidMount() {
        //this.filterByDate()
    }

    image(src) {
        return this.props.dirImage + src;
    }

    reload = () => {
        fetch(`?s=tbl_list&db=${this.props.db}&ajax=1`)
          .then(response => response.json())
          .then(json => this.setState({tables: json.page.tables}))
    }

    filterByDate() {
        let year = document.querySelector('[name="ds_year"]').value
        let month = document.querySelector('[name="ds_month"]').value
        let day = document.querySelector('[name="ds_day"]').value
        let date = new Date(year, month, day)

        let tables = Object.values(this.props.tables).filter((table, key) => {
            let now = new Date(table.Update_time);
            return now > date;
        })
        this.setState({tables})
    }

    render() {

        return (
          <div>
              <form action={"?db="+this.props.db} method="post" name="formTableList" id="formTableList">

                  <input type="hidden" name="tableMulty" value="1" />
                  <input type="hidden" name="action" value="" />

                  <TableList tables={this.state.tables} dirImage={this.props.dirImage} db={this.props.db} />

                  <div className="chbxAction">
                      <img src={this.image("arrow_ltr.png")} alt=""  />
                      <a href="#" onClick={this.chbx_action.bind(this, 'check')} id="chooseAll">выбрать все</a>  &nbsp;
                      <a href="#" onClick={this.chbx_action.bind(this, 'uncheck')}>очистить</a>
                  </div>

                  <div className="imageAction">
                      <u>Выбранные</u>
                      <img src={this.image("close.png")} alt="" onClick={this.msImageAction.bind(this, 'delete_all', '')} />
                      <img src={this.image("delete.gif")} alt="" onClick={this.msImageAction.bind(this, 'truncate_all', '')} />
                      <img src={this.image("copy.gif")} alt="" onClick={this.msImageAction.bind(this, 'copy_all', '')} />
                      <img src={this.image("b_tblexport.png")} alt="" onClick={this.msImageAction.bind(this, 'export_all', `?db=${this.props.db}&s=export`)} />

                      <select name="act" onChange={this.msImageAction.bind(this, 'auto', '')} >
                          <option></option>
                          <option value="check">проверить</option>
                          <option value="analyze">анализ</option>
                          <option value="optimize">оптимизировать</option>
                          <option value="repair">починить</option>
                          <option value="flush">сбросить кэш</option>
                      </select>

                      <input type="hidden" name="copy_struct" value="1" />
                      <input type="hidden" name="copy_data" value="1" />
                  </div>
              </form>
              <div className="links-block">
                  <a href="?s=tbl_list&action=full" title="Отобразить простую таблицу с полными данными всех таблиц, полученными с помощью запроса SHOW TABLE STATUS">Полная таблица</a>
                  <a href="?s=tbl_list&action=structure">Исследование структуры таблиц</a>
              </div>
              {this.props.showtableupdated > 0 &&
                <form className="showtableupdated">
                    Показать таблицы обновлённые с <DateSelector />
                    <input type="button" value="Показать!" onClick={this.filterByDate.bind(this)} />
                </form>
              }
          </div>
        );
    }
}