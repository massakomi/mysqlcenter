class Login extends React.Component {

    constructor(props) {
        super(props);
        this.state = {...props}
        this.checkCurrentSetting(true)
    }

    componentDidMount() {

    }

    changeCurrentSetting = (e) => {
        let newSetting = e.target.options[e.target.selectedIndex].value
        if (newSetting === this.state.current) {
            return
        }
        this.setState({'current': newSetting})
    }

    update = (e) => {
        let config = this.state.config
        config[this.state.current][e.target.name] = e.target.value
        this.setState({'config': config})
    }

    add = () => {
        let name = prompt('Введите название')
        if (!name) {
            return
        }

        let maxValue = 0
        for (let key in this.state.config) {
            if (!maxValue || key > maxValue) {
                maxValue = key
            }
        }
        maxValue ++

        let config = this.state.config
        config[maxValue] = this.defaults(name)
        this.setState({'config': config})
        this.checkCurrentSetting()
    }

    defaults(name) {
        return {
            name: name,
            driver: "mysql",
            host: "",
            user: "",
            password: "",
            port: "3306",
            database: "",
        }
    }

    rename() {
        let selector =  document.querySelector('.login select')
        if (selector.selectedIndex === -1) {
            alert('Не выбрано ничего')
            return
        }
        let selected = selector.options[selector.selectedIndex]
        let name = prompt('Введите название', selected.text)
        if (!name) {
            return
        }
        selected.text = name
        let config = this.state.config
        config[this.state.current].name = name
        this.setState({'config': config})
    }

    async save(mode, e) {
        e.preventDefault()
        const config = JSON.stringify(this.state.config);
        await msQuery(mode, { config: config, current: this.state.current }, function(data) {
            console.log(data)
        })
    }

    checkCurrentSetting(direct=false) {
        if (typeof(this.state.config[this.state.current]) == 'undefined') {
            let config = this.state.config
            config [this.state.current] = this.defaults("!error!")
            if (direct) {
                this.state.config = config
            } else {
                this.setState({'config': config})
            }
        }
    }

    render() {

        let options = []
        for (let key in this.state.config) {
            options.push(<option key={"opt-"+key} value={key}>{this.state.config[key].name}</option>)
        }

        return (
          <div className="login">
              <form>
                  <div>
                      <label>Хост</label><input name="host" type="text" onChange={this.update} value={this.state.config[this.state.current].host}/>
                  </div>
                  <div>
                      <label>Пользователь</label><input name="user" type="text" onChange={this.update} value={this.state.config[this.state.current].user}/>
                  </div>
                  <div>
                      <label>Пароль</label><input name="password" type="password" onChange={this.update} value={this.state.config[this.state.current].password}/>
                  </div>
                  <div>
                      <label>Порт</label><input name="port" type="number" onChange={this.update} value={this.state.config[this.state.current].port}/>
                  </div>
                  <div>
                      <label>База данных</label><input name="database" type="text" onChange={this.update} value={this.state.config[this.state.current].database}/>
                  </div>
                  <div>
                      <label></label>
                      <input type="button" onClick={this.save.bind(this, 'connectCheck')} defaultValue="Проверить"/>
                      <input type="button" onClick={this.save.bind(this, 'connectSave')} defaultValue="Сохранить"/>
                  </div>
              </form>
              <div className="list">
                  <select multiple defaultValue={[this.state.current]} onChange={this.changeCurrentSetting}>
                      {options}
                  </select>
                  <input type="button" onClick={this.add} defaultValue="Добавить"/>
                  <input type="button" onClick={this.rename.bind(this)} defaultValue="Переименовать"/>
              </div>
          </div>
        );
    }
}