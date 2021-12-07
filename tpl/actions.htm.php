<div id="root"></div>

<!-- Note: when deploying, replace "development.js" with "production.min.js". -->
<script src="/js/react.development.js" crossorigin></script>
<script src="/js/react-dom.development.js" crossorigin></script>
<script src="/js/react-babel.min.js"></script>
<script type="text/babel" src="/js/components.js"></script>

<script type="text/babel">


  class App extends React.Component {

    constructor(props) {
      super(props);
      this.state = {comment: false, renamename: props.table};
    }

    onChangeComment = (e) => {
      this.setState({'comment': e.target.value})
    }

    onChangeRenameName = (e) => {
      this.setState({'renamename': e.target.value})
    }

    render() {

      return (
          <table width="100%"  border="0" cellSpacing="0" cellPadding="3">
            <tbody><tr>
              <td><fieldset className="msGeneralForm">
                <legend>Переименовать таблицу в:</legend>
                <form action="<?php echo $DTQuery?>&action=tableRename" method="post" name="tableRename">
                  <input name="newName" type="text" onChange={this.onChangeRenameName} required value={this.state.renamename} />
                  <input type="submit" value="Выполнить!" disabled={!this.state.renamename} className="submit" />
                </form>
              </fieldset>

                <fieldset className="msGeneralForm">
                  <legend>Переместить таблицы в (база данных.таблица):</legend>
                  <form action="<?php echo $DTQuery?>&action=tableMove" method="post" name="tableMove">
                    <HtmlSelector data={this.props.dbs} name="newDB" auto="false" value="<?=$msc->db?>" />
                    .
                    <input name="newName" required type="text" defaultValue="<?php echo GET('table')?>" />
                    <input type="submit" value="Выполнить!" className="submit" />
                  </form>
                </fieldset>

                <fieldset className="msGeneralForm">
                  <legend>Скопировать таблицу в (база данных.таблица):</legend>
                  <form action="<?php echo $DTQuery?>&action=tableCopyTo" method="post" name="tableCopyTo">
                    <HtmlSelector data={this.props.dbs} name="newDB" auto="false" />
                    .
                    <input name="newName" type="text" required defaultValue="<?php echo GET('table')?>" />
                    <input type="submit" value="Выполнить!" className="submit" />
                  </form>
                </fieldset>

                <fieldset className="msGeneralForm">
                  <legend>Изменить кодировку таблицы</legend>
                  <form action="<?php echo $DTQuery?>&action=tableCharset" method="post" name="tableCharset">
                    <CharsetSelector charsets={this.props.charsets} />
                    <input type="submit" value="Выполнить!" />
                  </form>
                </fieldset>

                <fieldset className="msGeneralForm">
                  <legend>Комментарий к таблице</legend>
                  <form action="<?php echo $umaker->make('s', 'actions', 'action', 'tableComment')?>" method="post" name="tableComment">
                    <input name="comment" type="text" size="60" onChange={this.onChangeComment} defaultValue="<?php echo isset($row->Comment) ? $row->Comment : '' ?>" />
                    <input type="submit" value="Выполнить!" disabled={!this.state.comment} className="submit" />
                  </form>
                </fieldset>
                <fieldset className="msGeneralForm">
                  <legend>Изменить подрядок</legend>
                  <form action="<?php echo $umaker->make('s', 'actions', 'action', 'tableOrder')?>" method="post">
                    <HtmlSelector data={this.props.fields} name="field" />
                    <select name="order"><option value="">По возрастанию</option><option value="DESC">По убыванию</option></select>
                    <input type="submit" value="Выполнить!" className="submit" />
                  </form>
                </fieldset>
                <fieldset className="msGeneralForm">
                  <legend>Опции таблицы</legend>
                  <form action={this.props.tableOptionsUrl} method="post">
                    <input type="checkbox" name="checksum" defaultValue={this.props.checksum} /> checksum &nbsp; &nbsp;
                    <input type="checkbox" name="pack_keys" value="1" /> pack_keys
                    <input type="checkbox" name="delay_key_write" value="1" /> delay_key_write &nbsp;
                    <input name="auto_increment" type="text" size="3" defaultValue={this.props.ai} /> auto_increment
                    <input type="submit" value="Выполнить!" className="submit" />
                  </form>
                </fieldset>
              </td>
              <td valign="top">
                <div className="globalMenu">
                  <a href="<?php echo $DTQuery?>&action=tableCheck">Проверить таблицу</a> <br />
                  <a href="<?php echo $DTQuery?>&action=tableAnalize">Анализ таблицы</a> <br />
                  <a href="<?php echo $DTQuery?>&action=tableRepair">Починить таблицу</a> <br />
                  <a href="<?php echo $DTQuery?>&action=tableOptimize">Оптимизировать таблицу</a>  <br />
                  <a href="<?php echo $DTQuery?>&action=tableFlush">Сбросить кэш таблицы ("FLUSH")</a> <br />
                </div>

              </td>
            </tr></tbody>
          </table>
      );
    }
  }

  let options = {
    'table': '<?=GET('table')?>',
    'ai': '<?php echo isset($row->Auto_increment) ? $row->Auto_increment : '' ?>',
    'checksum': '<?=isset($row->Checksum) ? $row->Checksum : ''?>',
    'tableOptionsUrl': '<?=$umaker->make('s', 'actions', 'action', 'tableOptions')?>',
    'charsets': <?=json_encode($charsetList)?>,
    'dbs': <?=json_encode($dbs)?>,
    'fields': <?=json_encode(getFields($msc->table, true))?>
  }
  ReactDOM.render(
      <App {...options} />,
      document.getElementById('root')
  );

</script>