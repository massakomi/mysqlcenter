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
        <select name="charset" defaultValue="utf8">{opts}</select>
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

