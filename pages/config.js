class Config extends React.Component {

    constructor() {
        super();
        this.state = {messages: []};
    }

    update = async (e) => {
        e.preventDefault()
        let json = await msQuery('configUpdate', e.target.parentNode)
        this.setState({messages: json.messages})
    }

    restore = async (e) => {
        let json = await msQuery('configRestore', '')
        this.setState({messages: json.messages})
    }

    render() {

        const trs = Object.values(this.props.data).map((item) => {
            if (!item.includes('|')) {
                return;
            }
            let [name, title, value, type] = item.split('|')
            let input = ''
            if (type.includes('boolean')) {
                input = <input type="checkbox" name={name} value="1" defaultChecked={value != 0} />
            } else {
                input = <input type="text" name={name} defaultValue={value} />
            }
            return (
              <tr key={item.toString()}>
                  <td>{title}</td>
                  <td>{input}</td>
              </tr>
            )

        });

        return (
          <form>
              <Messages messages={this.state.messages} />
              <table>
                  <thead>
                  <tr>
                      <th>Параметр</th>
                      <th>Значение</th>
                  </tr>
                  </thead>
                  <tbody>{trs}</tbody>
              </table>
              <input type="button" onClick={this.update.bind(this)} value="Изменить" className="submit"/>
              <p style={{textAlign: 'center'}}><input type="button" onClick={this.restore} value="Восстановить значения по умолчанию" /></p>

          </form>
        );
    }
}