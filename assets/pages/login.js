import React, {useState} from 'react';
import {msQuery} from "../functions";

export function Login(props) {

    const changeCurrentSetting = (e) => {
        let newSetting = e.target.options[e.target.selectedIndex].value
        if (newSetting === current) {
            return
        }
        setCurrent(newSetting)
    }

    const update = (e) => {
        let c = Object.assign({}, config)
        c[current][e.target.name] = e.target.value
        setConfig(c)
    }

    const add = () => {
        let name = prompt('Введите название')
        if (!name) {
            return
        }

        let maxValue = 0
        for (let key in config) {
            if (!maxValue || key > maxValue) {
                maxValue = key
            }
        }
        maxValue ++

        let c = Object.assign({}, config)
        c[maxValue] = defaults(name)
        setConfig(c)
        checkCurrentSetting()
    }

    const defaults = name => ({
        name: name,
        driver: "mysql",
        host: "",
        user: "",
        password: "",
        port: "3306",
        database: "",
    });

    const rename = () => {
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
        let c = config
        c[current].name = name
        setConfig(c)
    };

    const save = async (mode, e) => {
        e.preventDefault()
        const c = JSON.stringify(config);
        await msQuery(mode, { config: c, current: current }, function(data) {
            if (mode === 'connectOpen') {
                location.href = '?s=db_list'
            }
            console.log(data)
        })
    };

    const checkCurrentSetting = (direct=false) => {
        if (typeof(config[current]) == 'undefined') {
            let c = config
            c [current] = defaults("!error!")
            if (direct) {
                config = c
            } else {
                setConfig(c)
            }
        }
    };

    let current, config, setCurrent, setConfig;
    if (props.current) {
        [current, setCurrent] = useState(props.current);
        [config, setConfig] = useState(props.config);
    } else {
        [current, setCurrent] = useState("0");
        [config, setConfig] = useState({
            "0": defaults("Default")
        });
    }

    checkCurrentSetting(true)

    let options = []
    for (let key in config) {
        options.push(<option key={"opt-"+key} value={key}>{config[key].name}</option>)
    }

    return (
      <div className="login flex">
          <form>
              <div>
                  <label>Хост</label><input name="host" type="text" onChange={update} value={config[current].host}/>
              </div>
              <div>
                  <label>Пользователь</label><input name="user" type="text" onChange={update}
                                                    value={config[current].user}/>
              </div>
              <div>
                  <label>Пароль</label><input name="password" type="password" onChange={update}
                                              value={config[current].password}/>
              </div>
              <div>
                  <label>Порт</label><input name="port" type="number" onChange={update} value={config[current].port}/>
              </div>
              <div>
                  <label>База данных</label><input name="database" type="text" onChange={update}
                                                   value={config[current].database}/>
              </div>
              <div>
                  <label>Драйвер</label>
                  <select name="driver"  onChange={update} value={config[current].driver}>
                      <option>mysql</option>
                      <option>pgsql</option>
                  </select>
              </div>
              <div>
                  <label></label>
                  <input type="button" onClick={save.bind(this, 'connectOpen')} defaultValue="Открыть"/>
                  <input type="button" onClick={save.bind(this, 'connectSave')} defaultValue="Сохранить"/>
              </div>
          </form>
          <div className="list">
              <select multiple defaultValue={[current]} onChange={changeCurrentSetting}>
                  {options}
              </select>
              <input type="button" onClick={add} defaultValue="Добавить"/>
              <input type="button" onClick={rename.bind(this)} defaultValue="Переименовать"/>
          </div>
      </div>
    );
}