
class TableObject extends React.Component {

    constructor(props) {
        super(props);
    }

    render() {

        if (typeof this.props.data != 'object') {
            return (
              <div style="color:red">Это не объект!</div>
            );
        }

        let rows = []
        let index = 0;
        for (let i in this.props.data) {
            rows.push(
              <tr key={index ++}>
                  <td>{i}</td>
                  <td>{this.props.data[i]}</td>
              </tr>
            )
        }

        return (
          <table className={this.props.className}>
              <tbody>
              {rows}
              </tbody>
          </table>
        );
    }
}

function FieldSet(props) {
    return (
      <fieldset className="msGeneralForm">
          <legend>{props.title}</legend>
          <form action={props.url + "&action=" + props.action} method="post" name={props.action}>
              {props.children}
              <input type="submit" value="Выполнить!" style={{marginLeft: '5px'}} />
          </form>
      </fieldset>
    )
}

function ProcessList(props) {

    function kill(id) {
        msQuery('killProcess', `id=${id}`)
    }

    let trs = []
    for (let key in props.processes) {
        let item = props.processes[key]
        trs.push(
          <tr key={`tr-${key}`}>
              <td><a href="#" onClick={kill.bind(this, item.Id)}>Kill</a></td>
              <td>{item.Id}</td>
              <td>{item.User}</td>
              <td>{item.Host}</td>
              <td>{item.db ? item.db : <i>нет</i>}</td>
              <td>{item.Command}</td>
              <td>{item.Time}</td>
              <td>{item.State ? item.State : '---'}</td>
              <td>{item.Info ? item.Info : '---'}</td>
          </tr>)
    }

    return (
      <table className="contentTable">
          <thead>
          <tr>
              <th></th>
              <th>td</th>
              <th>user</th>
              <th>host</th>
              <th>db</th>
              <th>command</th>
              <th>time</th>
              <th>status</th>
              <th>sqlQuery</th>
          </tr>
          </thead>
          <tbody>
          {trs}
          </tbody>
      </table>
    )
}



class Server_variables extends React.Component {

    constructor(props) {
        super(props);
        this.state = {sessionVars: [], globalVars: []}
    }

    async loadAll() {
        let sql = 'SHOW SESSION VARIABLES';
        let mode = 'querysql'
        let type = 'pair-value'
        let sessionVars = await msQuery(mode, {sql, type})
        //console.log(sessionVars)
        sql = 'SHOW GLOBAL VARIABLES';
        mode = 'querysql'
        type = 'pair-value'
        let globalVars = await msQuery(mode, {sql, type})
        this.setState({globalVars, sessionVars})
    }

    wrap = (s, cmp) => {
        if (s === undefined || cmp === s) {
            return null
        } else {
            return <span title={s}>{s.substr(0, 20)}</span>
        }

    }

    componentDidMount () {
        this.loadAll()
    }

    render() {

        let trs = []
        let i =0;
        for (let prop in this.state.sessionVars) {
            i ++
            trs.push((
              <tr key={i}>
                  <td><b>{prop.replace('_', ' ')}</b></td>
                  <td>{this.wrap(this.state.sessionVars[prop])}</td>
                  <td>{this.wrap(this.state.globalVars[prop], this.state.sessionVars[prop])}</td>
              </tr>
            ))
        }

        return (
          <table className="contentTable">
              <thead>
              <tr>
                  <th>Свойство</th>
                  <th>session var</th>
                  <th>global var</th>
              </tr>
              </thead>
              <tbody>
              {trs}
              </tbody>
          </table>
        );
    }
}


function UserInfo(props) {
    return (
      <React.Fragment>
          <fieldset className="msGeneralForm">
              <legend>Пользователи</legend>
              <Table data={props.users} />
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>SHOW GRANTS</legend>
              <div className="mb-5">Список привилегий, предоставленных аккаунту, который вы используете для соединения с сервером (FOR CURRENT_USER)</div>
              <Table data={props.grants} />
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>SHOW PRIVILEGES</legend>
              <div className="mb-5">Список системных привилегий, которые поддерживает MySQL сервер. Точный список привилегий зависит от версии вашего сервера.</div>
              <Table data={props.privileges} />
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>SHOW ENGINES</legend>
              <div className="mb-5">SHOW ENGINES displays status information about the server\'s storage engines. This is particularly useful for checking whether a storage engine is supported, or to see what the default engine is</div>
              <Table data={props.engines} />
          </fieldset>
          <fieldset className="msGeneralForm">
              <legend>Переменные сервера</legend>
              <Server_variables />
          </fieldset>
      </React.Fragment>
    )
}

class Actionsdb extends React.Component {

    fullinfo = () => {
        fetch(this.props.url + '&ajax=1&act=fullinfo')
          .then(response => response.json())
          .then(json => this.setState({dbInfo: json.page.dbInfo}))
    }

    constructor(props) {
        super(props);
        this.state = {dbInfo: props.dbInfo};
    }

    render() {

        //<input name="auto" type="checkbox" value="1" checked> Добавить значение AUTO_INCREMENT<br>
        //<input name="limit" type="checkbox" value="1"> Добавить ограничения<br>

        const operations = (
          <React.Fragment>
              <FieldSet title="Переименовать базу данных в:" action="dbRename" {...this.props}>
                  <input name="newName" type="text" required defaultValue={this.props.db}/>
              </FieldSet>
              <FieldSet title="Копировать базу данных в:" action="dbCopy" {...this.props}>
                  <input name="newName" required type="text" defaultValue={this.props.db + "_copy"}/><br/>
                  <input name="option" type="radio" value="struct"/> Только структуру <br/>
                  <input name="option" type="radio" value="all" defaultChecked/> Структура и данные <br/>
                  <input name="option" type="radio" value="data"/> Только данные <br/>
                  <input name="switch" type="checkbox" value="1"/> Перейти к скопированной БД <br/><br/>
              </FieldSet>
              <FieldSet title="Изменить кодировку базы данных:" action="dbCharset" {...this.props}>
                  <CharsetSelector charsets={this.props.charsets}/>
              </FieldSet>
              <fieldset className="msGeneralForm">
                  <legend>Информация о базе данных</legend>
                  <TableObject data={this.state.dbInfo} className="contentTable"/>
                  <br/>
                  <a href="#" onClick={this.fullinfo}>Показать полную информацию</a>
                  <br/>
                  <a href={`${this.props.url}&users=1`}>Информация о пользователях и правах</a>
              </fieldset>
              <fieldset className="msGeneralForm">
                  <legend>Список процессов</legend>
                  <ProcessList processes={this.props.processes} url={this.props.url}/>
              </fieldset>
          </React.Fragment>
        )

        return (
          <React.Fragment>
              {this.props.users ? <UserInfo {...this.props} />
                : operations}
          </React.Fragment>
        );
    }
}