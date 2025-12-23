class Login extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            driver: "mysql",
            host: "mysql-5.7",
            user: "root",
            password: "",
            port: "3306",
            database: "",
        };
    }

    componentDidMount() {

    }

    add() {
        let name = prompt('Введите название')
        if (!name) {
            return
        }
        let maxValue = 0
        $('select option').each(function() {
            if (!maxValue || this.value > maxValue) {
                maxValue = this.value
            }
        })
        maxValue ++
        $('select').append(`<option value="${maxValue}">${name}</option>`)
    }

    rename() {
        let selected = $('select option:selected')
        if (!selected.length) {
            alert('Не выбрано ничего')
            return
        }
        let name = prompt('Введите название', selected.text())
        if (!name) {
            return
        }
        $('select option:selected').text(name)
    }

    connect(e) {
        e.preventDefault()
        msQuery('', e.target.closest('form'), function(data) {
            console.log(data)
        })
    }

    render() {
        return (
          <div className="login">
              <form>
                  <div>
                      <label>Хост</label><input name="host" type="text" defaultValue={this.state.host}/>
                  </div>
                  <div>
                      <label>Пользователь</label><input name="user" type="text" defaultValue={this.state.user}/>
                  </div>
                  <div>
                      <label>Пароль</label><input name="password" type="password" defaultValue={this.state.password}/>
                  </div>
                  <div>
                      <label>Порт</label><input name="port" type="number" defaultValue={this.state.port}/>
                  </div>
                  <div>
                      <label>База данных</label><input name="port" type="database" defaultValue={this.state.database}/>
                  </div>
                  <div>
                      <label></label>
                      <input type="button" onClick={this.connect.bind(this)} value="Открыть"/>
                  </div>
              </form>
              <div className="list">
                  <select multiple>
                      <option value="1">Default</option>
                  </select>
                  <input type="button" onClick={this.add.bind(this)} value="Добавить"/>
                  <input type="button" onClick={this.rename.bind(this)} value="Переименовать"/>
              </div>
          </div>
        );
    }
}