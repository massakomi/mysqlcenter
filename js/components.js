class CharsetSelector extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {

    let opts = [], i = 0
    for (let charset in this.props.charsets) {
      let title = null;
      let info = this.props.charsets[charset]
      if (typeof info == 'object') {
        title = info.Description + ' (default: '+info['Default collation']+')'
      }
      opts.push(<option key={i++} title={title}>{charset}</option>)
    }

    return (
        <select name="charset" defaultValue={this.props.value ? this.props.value : 'utf8'}>{opts}</select>
    );
  }
}

class HtmlSelector extends React.Component {

  constructor(props) {
    super(props);
  }

  onChange = (e) => {
    if (this.props.auto) {
      let value = e.target.options[e.target.selectedIndex].value
      if (value) {
        console.log(value)
      }
    }
  }

  render() {

    let opts = [], i = 0
    for (let key in this.props.data) {
      let title = this.props.data[key]
      let value = title
      if (this.props.keyValues) {
        value = key;
      }
      opts.push(<option value={value} key={i++}>{title}</option>)
    }

    return (
        <select onChange={this.props.onChange ? this.props.onChange : this.onChange.bind(this)}
                name={this.props.name}
                multiple={this.props.multiple}
                defaultValue={this.props.value}
                className={this.props.class}>{opts}
        </select>
    );
  }
}


function Messages(props) {

  const MessageText = (item) => {
    let extra = []
    if (item.sql) {
      extra.push(<div key="v1" className="sqlQuery">{item.sql}</div>)
      if (item.rows) {
        extra.push(<div key="v2" style={{color: '#ccc'}}>затронуто рядов: {item.rows}</div>)
      }
      if (item.error) {
        extra.push(<div key="v3" className="mysqlError"><b>Ошибка:</b> {item.error}</div>)
      }
    }
    return (<div style={{color: item.color}}>{item.text}{extra}</div>)
  }

  const CloseMessage = (e) => {
    $(e.target).closest('tr').next().toggle()
  }

  return <div className="messages">{props.messages.map((item, key) =>
    (
      <table className="globalMessage" key={key}>
        <tbody>
        <tr><th>Сообщение <a href="#" className="hiddenSmallLink" style={{color: 'white'}} onClick={CloseMessage.bind(this)}>close</a></th></tr>
        <tr><td>{MessageText(item)}</td></tr>
        </tbody>
      </table>
    )
  )}</div>
}