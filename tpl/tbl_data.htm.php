<style>
/* Страницы-ссылки для tbl_data */
DIV.contentPageLinks { margin:5px 0 0;}
DIV.contentPageLinks a { text-decoration:none; font-size:10px; margin: 0; padding: 2px 5px; border:1px solid #eee; margin-right: 2px}
DIV.contentPageLinks a.cur { background-color: #0000FF; color:#FFFFFF}
/* Селектор для tbl_data */
SELECT.miniSelector { font-size:12px;}
SELECT.miniSelector OPTION { font-size:12px;}
</style>

<?php echo $table->make()?>

<div id="root"></div>

<!-- Note: when deploying, replace "development.js" with "production.min.js". -->
<script src="/js/react.development.js" crossorigin></script>
<script src="/js/react-dom.development.js" crossorigin></script>
<script src="/js/react-babel.min.js"></script>
<script type="text/babel" src="/js/components.js"></script>


<script type="text/babel">

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
      let linksRange = <?=(int)MS_LIST_LINKS_RANGE?>;

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

      return (
        <div className="contentPageLinks">
          {links}
          <HtmlSelector data={this.pagesObj()} auto="true" class="miniSelector" value={getPart} keyValues="true" />
        </div>
      );
    }
  }

  class App extends React.Component {

    constructor(props) {
      super(props);
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

    render() {

      return (
          <div>

            <form action={this.props.url.replace('#s#', 'tbl_data')} method="post" name="formTableRows" id="formTableRows">
              <input type="hidden" name="rowMulty" value="1" />
              <input type="hidden" name="action" value="" />

              <TableLinks count={this.props.count} part={this.props.part} />

              <div className="chbxAction">
                <img src="<?php echo $p?>arrow_ltr.png" alt="" border="0" align="absmiddle" />
                <a href="#" onClick={this.chbx_action.bind(this, 'check')}>выбрать все</a>  &nbsp;
                <a href="#" onClick={this.chbx_action.bind(this, 'uncheck')}>очистить</a>
              </div>

              <div className="imageAction">
                <table width="100%" border="0" cellSpacing="0" cellPadding="0">
                  <tbody>
                  <tr>
                    <td>
                      <u>Выбранные</u>
                      <img src="<?php echo MS_DIR_IMG?>edit.gif" alt="" border="0" onClick={this.msImageAction.bind(this, 'editRows', 'tbl_change')} />
                      <img src="<?php echo MS_DIR_IMG?>close.png" alt="" border="0" onClick={this.msImageAction.bind(this, 'deleteRows', '')} />
                      <img src="<?php echo MS_DIR_IMG?>copy.gif" alt="" border="0" onClick={this.msImageAction.bind(this, 'copyRows', '')} />
                      <img src="<?php echo MS_DIR_IMG?>b_tblexport.png" alt="" border="0" onClick={this.msImageAction.bind(this, 'exportRows', 'export')} />
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

  let options = {
    'table': '<?=$msc->table?>',
    'count': <?=$count?>,
    'part': <?=$part?>,
    'url': '<?=$umaker->make('s', '#s#')?>',
    'showtablecompare': '<?=conf('showtablecompare')?>',
    'dbs': <?=json_encode(Server::getDatabases())?>
  }
  ReactDOM.render(
      <App {...options} />,
      document.getElementById('root')
  );

</script>



