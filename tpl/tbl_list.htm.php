<script type="text/babel">

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

    /*checkboxer = () => {

    }*/

    image(src) {
      return <img src={this.props.dirImage + src} alt="" />
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
      let size = Math.round((parseInt(table.Data_length) + parseInt(table.Index_length)) / 1024);
      this.sumSize += parseInt(size);
      this.sumRows += parseInt(table.Rows);
      // Сборка значения рядов
      let msquery = "db=<?=$msc->db?>&table="+table.Name;
      let idRow = "row" + sumTable;
      let idChbx = 'table_' + table.Name
      let engine = table.Engine === 'MyISAM' ? <span style={{color: '#ccc'}}>MyISAM</span> : table.Engine;

      return (
          <tr key={table.Name} id={idRow}>
            <td id={'row'+sumTable+'-0'}><input name="table[]" type="checkbox" value={table.Name} id={idChbx} className="cb" onClick={checkboxer.bind(this, sumTable, '#row')} /></td>
            <td id={'row'+sumTable+'-1'} className="tbl"><label htmlFor={idChbx} onDoubleClick={this.renameTable.bind(this, table.Name, 'row'+sumTable+'-1')}>{valueName}</label></td>
            <td id={'row'+sumTable+'-2'}><a href={'/?db=<?=$msc->db?>&table='+table.Name+'&s=tbl_data'} title="Обзор таблицы">{this.image("actions.gif")}</a></td>
            <td id={'row'+sumTable+'-3'}><a href={'/?db=<?=$msc->db?>&table='+table.Name+'&s=tbl_struct'} title="Структура таблицы">{this.image("generate.png")}</a></td>
            <td id={'row'+sumTable+'-4'}>
              <a href="#" onClick={msQuery.bind(this, 'tableTruncate', msquery+'&id='+idRow+'-8&id2='+idRow+'-9')} title="Очистить таблицу">{this.image("delete.gif")}</a>
            </td>
            <td id={'row'+sumTable+'-5'}>
              <a href="#" onClick={msQuery.bind(this, 'tableDelete', msquery+'&id='+idRow+'-8&id2='+idRow+'-9')} title="Удалить таблицу">{this.image("close.png")}</a>
            </td>
            <td id={'row'+sumTable+'-6'} className="rig">{table.Rows}</td>
            <td id={'row'+sumTable+'-7'} className="rig">{this.formatSize(size)}</td>
            <td id={'row'+sumTable+'-8'}>{updateTime}</td>
            <td id={'row'+sumTable+'-9'} className="num">{table.Auto_increment}</td>
            <td id={'row'+sumTable+'-10'}><span>{engine}</span></td>
            <td id={'row'+sumTable+'-11'} className="rig"><span title={table.Collation} style={{color: '#aaa'}}>{table.Collation.substr(0, table.Collation.indexOf("_"))}</span></td>
          </tr>
      )
    }

    formatSize(bytes) {
      if (bytes < Math.pow(1024, 1)) {
        return bytes + " b";
      } else if (bytes < Math.pow(1024, 2)) {
        return (bytes / Math.pow(1024, 1)).toFixed(2) + ' Kb';
      } else if (bytes < Math.pow(1024, 3)) {
        return (bytes / Math.pow(1024, 2)).toFixed(2) + ' Mb';
      } else if (bytes < Math.pow(1024, 4)) {
        return (bytes / Math.pow(1024, 3)).toFixed(2) + ' Gb';
      }
    }

    renameTable(tableOld, id, e) {
      let newName = prompt('Новое имя', tableOld)
      if (newName) {
        let q = 'db=<?=$msc->db?>&s=tbl_list&table='+tableOld+'&newName=' + newName + '&id='+id;
        msQuery('tableRename', q);
      }
    }

    render() {

      let tables = Object.values(this.props.tables)
      this.sumSize = 0;
      this.sumRows = 0;
      let trs = tables.map((table, key) => this.renderRow(table, key))

      return (
          <table className="contentTable">
            <thead>
            <tr>
              <th>&nbsp;</th>
              <th><b>Таблица</b></th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th><b>Рядов</b></th>
              <th><b>Размер</b></th>
              <th><b>Дата обновления</b></th>
              <th><b>Ai</b></th>
              <th>Engine</th>
              <th>Cp</th>
            </tr></thead>
            <tbody>
            {trs}
            <tr>
              <td>&nbsp;</td>
              <td className="tbl">{tables.length} таблиц</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td className="rig">{Number(this.sumRows).toFixed(0)}</td>
              <td className="rig">{this.formatSize(this.sumSize)}</td>
              <td>&nbsp;</td>
              <td className="num">&nbsp;</td>
              <td>&nbsp;</td>
              <td className="rig">&nbsp;</td>
            </tr></tbody>
          </table>
      );
    }
  }

</script>


<div id="root"></div>

<!-- Note: when deploying, replace "development.js" with "production.min.js". -->
<script src="/js/react.development.js" crossorigin></script>
<script src="/js/react-dom.development.js" crossorigin></script>
<script src="/js/react-babel.min.js"></script>


<script type="text/babel">


  class App extends React.Component {

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
      let year = $('[name="ds_year"]').val()
      let month = $('[name="ds_month"]').val()
      let day = $('[name="ds_day"]').val()
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

              <TableList tables={this.state.tables} dirImage={this.props.dirImage} />

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

                <a href={`?db=${this.props.db}&makeInnodb=1`} style={{margin: '0 10px'}}>Конвертировать все в Innodb</a>

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
              <a href="#" onClick={this.reload.bind(this)}>reload</a>
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

  let options = <?=json_encode($pageProps)?>;
  ReactDOM.render(
      <App {...options} />,
      document.getElementById('root')
  );

</script>


<style type="text/css">
    form.showtableupdated select, form.showtableupdated [type="submit"] {margin-left:3px}
    .links-block {padding:10px 0; margin-bottom:10px; font-size:14px;}
    .links-block a + a {margin-left: 10px}
    table.contentTable tr:nth-child(odd) {background-color: #eee;}
</style>

