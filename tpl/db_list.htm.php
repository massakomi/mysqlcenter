<div id="root"></div>

<script type="text/babel">



  class TableFull extends React.Component {

    constructor(props) {
      super(props);
    }

    dbDelete(db) {
      msQuery('dbDelete', `db=${db}&id=db${db}`)
    }

    dbHide(db, action) {
      msQuery('dbHide', `db=${db}&id=db${db}&action=${action}`)
    }

    render() {

      let nz = Intl.NumberFormat(undefined, {'maximumFractionDigits': 0});
      let nf = Intl.NumberFormat(undefined, {'minimumFractionDigits': 1, 'maximumFractionDigits': 1});
      let trs = [], countTotalTables = 0, countTotalSize = 0, countTotalRows = 0;
      for (let i = 0; i < this.props.databases.length; i++) {
        let db = this.props.databases[i]['name'];
        let href = `/?db=${db}&s=tbl_list`
        let idRow = "db"+db;

        let extra = this.props.databases[i]['extra'];
        let countTables = 0, countSize = 0, countRows = 0, updateTime = 0;
        for (let status of extra) {
          countTables ++;
          if (status.Update_time) {
            let ts = Date.parse(status.Update_time) / 1000;
            if (ts > updateTime) {
              updateTime = ts
            }
          }
          let rows = parseInt(status.Rows)
          if (!isNaN(rows)) {
            countRows += rows
          }
          let size = ((parseInt(status.Data_length) + parseInt(status.Index_length)) / 1024)
          if (!isNaN(size)) {
            countSize += size
          }
        }

        countTotalTables += countTables
        countTotalSize += countSize
        countTotalRows += countRows

        if (updateTime) {
          /*$updateTime = date2rusString(MS_DATE_FORMAT, $updateTime);
          if (strpos($updateTime, 'дня') !== false || strpos($updateTime, 'ера') !== false) {
            $updateTime = "<b>$updateTime</b>";
          }*/
          updateTime = new Date(updateTime * 1000)
          updateTime = updateTime.toLocaleString()
        }

        trs.push(
            <tr key={i}>
              <td><input name="databases[]" type="checkbox" value={db} className="cb" /></td>
              <td><a href={href} title="Структура БД" id={idRow}>{db}</a></td>
              <td><a href="#" onClick={this.dbDelete.bind(this, db)} title={'Удалить '+db}><img src={"/" + this.props.folder + "close.png"} alt="" border="0" /></a></td>
              <td>{countTables}</td>
              <td>{updateTime}</td>
              <td>{nz.format(countRows)}</td>
              <td>{nf.format(countSize)}</td>
            </tr>
        )
      }

      return (
          <table className="contentTable" id="structureTableId">
            <thead>
            <tr>
              <th></th>
              <th>Название</th>
              <th></th>
              <th>Таблиц</th>
              <th>Обновлено</th>
              <th>Рядов</th>
              <th>Размер</th>
            </tr>
            </thead>
            <tbody>
            {trs}
            </tbody>
            <tfoot>
            <tr>
              <td></td>
              <td></td>
              <td></td>
              <td>{countTotalTables}</td>
              <td></td>
              <td>{nz.format(countTotalRows)}</td>
              <td>{nf.format(countTotalSize)}</td>
            </tr>
            </tfoot>
          </table>
      );
    }
  }

    class Table extends React.Component {

        constructor(props) {
            super(props);
        }

        dbDelete(db) {
            msQuery('dbDelete', `db=${db}&id=db${db}`)
        }

        dbHide(db, action) {
            alert('hide')
            msQuery('dbHide', `db=${db}&id=db${db}&action=${action}`)
        }

        render() {
            let mscExists = this.props.databases.includes('mysqlcenter')
            let trs = []
            for (let i = 0; i < this.props.databases.length; i++) {
                let db = this.props.databases[i];
                let styles = {}
                let action = '';
                if (mscExists && this.props.hiddens && this.props.hiddens.includes(db)) {
                    styles = {color: '#ccc'}
                    action = 'show'
                }
                let href = `/?db=${db}&s=tbl_list`
                let idRow = "db"+db;
                // Добавляем в комментарий имя БД, чтобы в автотестах определить, куда кликать при проверке удаления
                trs.push(
                    <tr key={i}>
                        <td><input name="databases[]" type="checkbox" value={db} className="cb" /></td>
                        <td><a href={href} title="Структура БД" id={idRow} style={styles}>{db}</a></td>
                        <td>
                            <a href="#" onClick={this.dbDelete.bind(this, db)} title={'Удалить '+db}><img src={"/" + this.props.folder + "close.png"} alt="" border="0" /></a> &nbsp;
                            <a href={'/?db=' + db + '&s=actions'} title="Изменить"><img src={"/" + this.props.folder + "edit.gif"} alt="" border="0" /></a> &nbsp;
                            {mscExists ?
                                <a href="#" onClick={this.dbHide.bind(this, db, action)} title={'Спрятать/показать ' + db}><img src={"/" + this.props.folder + "open-folder.png"} alt="" border="0" width="16" /></a> : null}
                        </td>
                    </tr>
                )
            }

            return (
                <table className="contentTable" id="structureTableId">
                    <thead>
                    <tr>
                        <th></th>
                        <th>Название</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    {trs}
                    </tbody>
                </table>
            );
        }
    }

    class ColumnLeft extends React.Component {

        constructor(props) {
            super(props);
            this.state = {value: 'wait'};
        }

        msImageAction = (param, actionReplace) => {
            if (typeof actionReplace == 'string') {
                actionReplace = this.props.url + '?s=' + actionReplace
            } else {
                actionReplace = ''
            }
            msImageAction('formDatabases', param, actionReplace)
        }

        chbxAction(opt) {
            chbx_action('formDatabases', opt, 'databases[]')
        }

        render() {

            return (
                <div>
                    <form action="?s=db_list" method="post" name="formDatabases" id="formDatabases">
                        <input type="hidden" name="dbMulty" value="1" />
                        <input type="hidden" name="action" value="" />
                          {!this.props.showFullInfo ?
                              <Table folder={this.props.folder} databases={this.props.databases} hiddens={this.props.hiddens} /> :
                              <TableFull folder={this.props.folder} databases={this.props.databases} hiddens={this.props.hiddens} />}
                    </form>

                    <div className="chbxAction">
                        <img src={"/" + this.props.folder + "arrow_ltr.png"} alt="" border="0" align="absmiddle" />
                        <a href="#" onClick={this.chbxAction.bind(this, 'check')}>выбрать все</a>  &nbsp;
                        <a href="#" onClick={this.chbxAction.bind(this, 'uncheck')}>очистить</a>
                    </div>

                    <div className="imageAction">
                        <u>Выбранные</u>
                        <input type="image" src={"/" + this.props.folder + "close.png"} onClick={this.msImageAction.bind(this, 'dbDelete')} title="Удалить базы данных" />
                        <input type="image" src={"/" + this.props.folder + "copy.gif"} onClick={this.msImageAction.bind(this, 'dbCopy')} title="Скопировать базы данных по шаблону {db_name}_copy" />
                        <input type="image" src={"/" + this.props.folder + "b_tblexport.png"} onClick={this.msImageAction.bind(this, 'exportDatabases', 'export')} title="Перейти к экспорту баз данных" />
                        <input type="image" src={"/" + this.props.folder + "fixed.gif"} onClick={this.msImageAction.bind(this, 'db_compare', 'db_compare')} title="Сравнить выбранные базы данных" />
                    </div>
                </div>
            );
        }
    }




    class ColumnRight extends React.Component {

        constructor(props) {
            super(props);
            this.state = {
                database: '',
                passwordField: '',
                password2Field: '',
                formDisabled: true
            };
            this.updateLoginName = this.updateLoginName.bind(this);
            this.setPasswordField = this.setPasswordField.bind(this);
            this.setPasswordField2 = this.setPasswordField2.bind(this);
        }

        updateLoginName(event) {
            this.setState({database: event.target.value})
        }

        setPasswordField(event) {
            this.setState({passwordField: event.target.value})
            let s = event.target.value !== this.state.passwordField2
            this.setState({formDisabled: s})
        }

        setPasswordField2(event) {
            this.setState({password2Field2: event.target.value})
            let s = this.state.passwordField !== event.target.value
            this.setState({formDisabled: s})
        }



        render() {

            let tableLink;
            if (!this.props.showFullInfo) {
                tableLink = <a href={`?s=db_list&db=${this.props.dbname}&mode=full`} title="Сканирует все таблицы всех баз данных и выводит количество таблиц, размер, дату обновления и количество рядов">Показать полную таблицу</a>
            } else {
                tableLink = <a href={`?s=db_list&db=${this.props.dbname}`}>Показать краткую таблицу</a>
            }

            return (
                <div>
                    <DbCreateForm />

                    <div className="mt-10">
                        {tableLink} <br />
                        <a href={`?s=db_list&db=${this.props.dbname}&mode=speedtest`}>Тест скорости</a>
                    </div>

                    <div className="mt-10">{this.props.appName}</div>
                    <div>Версия {this.props.appVersion}</div>
                    <div>Хост: {this.props.dbHost}</div>

                    <div className="mt-10">Версия сервера: {this.props.mysqlVersion}</div>
                    <div>Версия PHP: {this.props.phpversion}</div>
                    <div>БД: {this.props.dbname}<br /></div>

                    <fieldset className="mt-10">
                        <legend>Добавить пользователя</legend>
                        <form action="?s=users&action=add" method="post">
                            <div className="mb-5"><input name="rootpass" type="text" /> Пароль админа</div>
                            <div className="mb-5"><input name="database" type="text" required={true} id="databaseField" onKeyUp={this.updateLoginName} /> Имя базы данных</div>
                            <div className="mb-5"><input name="databaseuser" id="unameField" type="text" required={true} defaultValue={this.state.database}  /> Логин пользователя</div>
                            <div className="mb-5"><input name="userpass" type="password" id="passwordField" required={true} onChange={this.setPasswordField} /> Пароль</div>
                            <div className="mb-5"><input name="userpass2" type="password" id="password2Field" required={true} onChange={this.setPasswordField2} /> Пароль еще раз</div>
                            <div className="mb-5"><input type="submit" value="Добавить" disabled={this.state.formDisabled} id="submitBtnId" /></div>
                        </form>
                    </fieldset>
                </div>
            );
        }
    }

    function DbCreateForm(props) {
        return  (
            <fieldset className="msGeneralForm">
                <legend>Создание базы данных</legend>
                <form action="?s=tbl_list&action=dbCreate" method="post">
                    <input name="dbName" type="text" defaultValue="" />
                    <button type="submit">Создать!</button>
                </form>
            </fieldset>
        )
    }

    function App(props) {
        return  (
            <table width="100%" border="0" cellSpacing="0" cellPadding="3">
                <tbody>
                <tr>
                    <td valign="top">
                        <ColumnLeft folder={props.folder} url={props.url} showFullInfo={props.showFullInfo} databases={props.databases} hiddens={props.hiddens} />
                    </td>
                    <td valign="top">
                        <ColumnRight appName={props.appName} appVersion={props.appVersion} dbHost={props.dbHost} showFullInfo={props.showFullInfo} dbname={props.dbname}
                                     phpversion={props.phpversion} mysqlVersion={props.mysqlVersion} />
                    </td>
                </tr></tbody>
            </table>
        )
    }

    let options = <?=json_encode($pageProps)?>;

    ReactDOM.render(
        <App {...options} />,
        document.getElementById('root')
    );



</script>

