<?php
/*$links = getLinks($count, $part);
echo $links;
echo $table->make()*/
?>

<div id="root"></div>


<script type="text/babel">


  function TableHeader(props) {

    let headers = getTableHeaders(props.fieldsEx, !props.directSQL, props.headWrap);

    return (
      <table className="contentTable interlaced">
        <thead>
        <tr valign="top">
          <th><a href={umaker({'fullText': '1'}, true)} title="Показать полные значения всех полей и убрать переносы заголовков полей" className="hiddenSmallLink" style={{color:'white'}}>full</a></th>
          <th></th>
          <th></th>
          {headers.map((h, k) =>
            <th key={k}>{h}</th>
          )}
        </tr>
        </thead>
        <tbody>
        {props.children}
        </tbody>
      </table>
    );
  }

  class Table extends React.Component {

    constructor(props) {
      super(props);
    }


    render() {

      // Собираем массив имён полей, и также массив имён только ключевых полей
      let pk = [], fieldNames = []
      Object.values(this.props.fields).map(function(v) {
        fieldNames.push(v.Field)
        if (v.Key.indexOf('PRI') > -1) {
          pk.push(v.Field)
        }
      })

      // fields for header
      let fields = this.props.fields;
      if (this.props.directSQL) {
        fields = []
        for (let field in this.props.data[0]) {
          let a = {}
          a.Field = field;
          a.Type = $.isNumeric(this.props.data[0][field]) ? 'int' : 'varchar';
          fields.push(a)
        }
      }

      // Собираем ряды
      let trs = []
      let j = 0
      for (let row of this.props.data) {

        // определение уникального ид ряда
        let pkValues = []
        if (pk.length > 0) {
          for (let pkCurrent of pk) {
            if (!row[pkCurrent]) {
              console.log(`Hey! Ключевого поля ${pkCurrent} не найдено в таблице!?`)
              continue;
            }
            pkValues.push(`${pkCurrent}="${row[pkCurrent]}"`);
          }
        } else {
          for (let pkCurrent of fieldNames) {
            if (!row[pkCurrent]) {
              continue;
            }
            pkValues.push(`${pkCurrent}="${row[pkCurrent]}"`);
          }
        }
        let idRow = encodeURIComponent(pkValues.join(' AND '));

        // создание ссылок на действия
        let u1 = umaker({s: 'tbl_change', row: idRow});
        let u2 = umaker({s: 'tbl_data', row: idRow});
        let values = [
          <input name="row[]" type="checkbox" value={idRow} className="cb" id={`c${idRow}`} onClick={checkboxer.bind(this, j, '#row')} />,
          <a href={u1} title="Редактировать ряд"><img src={`${this.props.dirImage}edit.gif`} alt="" border="0" /></a>,
          <a href="#" onClick={msQuery.bind(this, 'deleteRow', `${u2}&id=row${j}`)} title="Удалить ряд"><img src={`${this.props.dirImage}close.png`} alt="" border="0" /></a>
        ]

        // загрузка данных
        let i = 0
        for (let k in row) {
          let type = "varchar";
          if (fields[i]) {
            type = fields[i].Type
          }
          let val = processRowValue(row[k], type, this.props.textCut)
          if (k === 'query') {
            //val = '<a href="http://yandex.ru/yandsearch?text='.$v.'" target="_blank">'.$val.'</a>';
          }
          values.push(val)
          i ++
        }

        let tds = [];
        let z = 0;
        for (let value of values) {
          tds.push(<td key={z}>{value}</td>)
          z ++;
        }
        trs.push(
          <tr key={j}>{tds}</tr>
        )
        j ++
      }

      return (
        <TableHeader {...this.props} fieldsEx={fields}>
          {trs}
        </TableHeader>
      );
    }
  }

  class TableLinks extends React.Component {

    constructor(props) {
      super(props);
      this.state = {value: 'wait'};
    }

    pagesObj() {
      let pagesNums = [30, 50, 100, 200, 300, 500, 1000, 'all']
      let pages = {}
      let u = new URL(location.href)
      for (let num of pagesNums) {
        u.searchParams.set('part', num)
        pages[u.href] = num
      }
      return pages;
    }

    render() {

      let getPart = new URL(location.href).searchParams.get('part');
      let getGo = new URL(location.href).searchParams.get('go');
      //let getSql = new URL(location.href).searchParams.get('sql');
      //let getOrder = new URL(location.href).searchParams.get('order');
      let linksRange = this.props.linksRange;

      let count = this.props.count
      let part = this.props.part
      if (count <= part && !getPart) {
        return false;
      }

      let links = []
      let countPages = Math.ceil(count / part)
      let currentPage = Math.ceil(getGo / part)
      let beginPage = Math.max(0, currentPage - linksRange)
      let endPage = Math.min(countPages, currentPage + linksRange)

    for (let i = beginPage; i < endPage; i ++) {
      let url = new URL(location.href)
      url.searchParams.set('go', i * part)
      if (getGo == i * part) {
        links.push(<a key={i} href={url} className="cur">{i + 1}</a>)
      } else {
        links.push(<a key={i} href={url}>{i + 1}</a>)
      }
    }

      let data = this.pagesObj();

      let selected = false;
      if (getPart) {
        for (let key in data) {
          if (data[key] == getPart) {
            selected = key
          }
        }
      }

      return (
        <div className="contentPageLinks">
          {links}
          <HtmlSelector data={data} auto="true" className="miniSelector" value={selected} keyValues="true" />
        </div>
      );
    }
  }

  class App extends React.Component {

    constructor(props) {
      super(props);
      console.log(props)
      this.state = {value: 'wait'};
    }

    chbx_action = (opt, e) => {
      e.preventDefault()
      chbx_action('formTableRows', opt, 'row[]')
    }

    msImageAction = (opt, url, e) => {
      console.log(typeof e)
      if (url) {
        url = this.props.url.replace('#s#', url)
      }
      msImageAction('formTableRows', opt, url)
    }

    componentDidMount() {
      jQuery('.contentTable TD').click(function(){
        var tr = jQuery(this).parent();
        var ch = tr.find('input').prop('checked');
        if (jQuery(this).index() == 0) {
          tr.toggleClass('selectedRow', ch);
          return true;
        }
        tr.toggleClass('selectedRow', !ch);
        jQuery(this).parent().find('input').attr('checked', !ch)
      });

      jQuery('.contentTable TR').dblclick(function(){
        location.href = jQuery(this).find('a').attr('href');
      });

      jQuery('.contentTable TD').click(function(){
        if (jQuery(this).index() <= 2) {
          return true;
        }
        if (globalCtrlKeyMode) {
          jQuery(this).html('<input type="text" value="'+jQuery(this).html()+'" id="editable">');
          jQuery('#editable').focus();
          jQuery('#editable').focusout(function() {
            var html = jQuery(this).val();
            jQuery(this).parent().html(html);
          });
        }
        return true;
      });
    }

    image(src) {
      return this.props.dirImage + src;
    }

    render() {

      return (
          <div>

            <form action={this.props.url.replace('#s#', 'tbl_data')} method="post" name="formTableRows" id="formTableRows">
              <input type="hidden" name="rowMulty" value="1" />
              <input type="hidden" name="action" value="" />

              <TableLinks count={this.props.count} linksRange={this.props.linksRange} part={this.props.part} />

              <Table {...this.props} />

              <div className="chbxAction">
                <img src={this.image("arrow_ltr.png")} alt="" border="0" align="absmiddle" />
                <a href="#" onClick={this.chbx_action.bind(this, 'check')}>выбрать все</a>  &nbsp;
                <a href="#" onClick={this.chbx_action.bind(this, 'uncheck')}>очистить</a>
              </div>

              <div className="imageAction">
                <table width="100%" border="0" cellSpacing="0" cellPadding="0">
                  <tbody>
                  <tr>
                    <td>
                      <u>Выбранные</u>
                      <img src={this.image("edit.gif")} alt="" border="0" onClick={this.msImageAction.bind(this, 'editRows', 'tbl_change')} />
                      <img src={this.image("close.png")} alt="" border="0" onClick={this.msImageAction.bind(this, 'deleteRows', '')} />
                      <img src={this.image("copy.gif")} alt="" border="0" onClick={this.msImageAction.bind(this, 'copyRows', '')} />
                      <img src={this.image("b_tblexport.png")} alt="" border="0" onClick={this.msImageAction.bind(this, 'exportRows', 'export')} />
                    </td>
                    <td align="right" id="tblDataInfoId">&nbsp;</td>
                  </tr>
                </tbody>
                </table>
              </div>
            </form>
            <form name="form1" method="post" action={this.props.url.replace('#s#', 'tbl_compare')}>
              <input type="hidden" name="table[]" value={this.props.table} />
                Сравнить таблицу с такой же в &nbsp;
                <HtmlSelector data={this.props.dbs} name="database" /> &nbsp;
                <input type="submit" value="Сравнить" />
            </form>
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



