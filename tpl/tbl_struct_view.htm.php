<div id="root"></div>

<script type="text/babel">

  class TableField extends React.Component {

    render() {

      let field = this.props.field
      let style = [];
      let title = '';

      if (field.Key == 'PRI') {
        if (field.Extra != '') {
          style.push({backgroundColor: '#FF6600'})
          title += 'priAI';
        } else {
          style.push({backgroundColor: '#FFCC00'})
          title += 'pri';
        }
      }
      if (field.Key == 'MUL') {
        style.push({backgroundColor: '#66CC99'})
        title += 'index';
      }
      if (field.Null != 'NO') {
        style.push({color: '#aaa'})
        title += ' null';
      } else {
        title += ' not null';
      }

      style = Object.fromEntries(style)
      return (
          <th style={style} title={title}>
            {field.Field}
          </th>
      );
    }
  }

  class TableData extends React.Component {

    strip_tags(item) {
      var div = document.createElement("div");
      div.innerHTML = item;
      return div.textContent || div.innerText || "";
    }

    // TODO wordwrap( mb_substr(strip_tags($v), 0, 100), 50, '<br />', true)
    wrap = (s) => s

    render() {
      const listItems = Object.values(this.props.data).map((items, key) => {
        return (
            <tr key={key + "index"} className="info grey">
              {Object.values(items).map((item, key) => {

                item = this.wrap(this.strip_tags(item))

                return <td key={key + "tds"}>{item}</td>
              })}
            </tr>
        )
      });

      return listItems
    }
  }

  class App extends React.Component {

    render() {

      let fieldVariants = {}
      for (const v of this.props.tables) {
        fieldVariants[v.Name] = v.Name
        let variant = v.Name.replace(/[s_]$/, '')
        fieldVariants[variant] = v.Name
        fieldVariants['id_'+variant] = v.Name
        fieldVariants[variant+'_id'] = v.Name
      }

      const listItems = Object.values(this.props.tables).map((table) => {
        let fields = table.fields

        let comparedTables = []
        Object.values(fields).map((field) => {
          if (field.Field in fieldVariants && fieldVariants[field.Field] != table.Name) {
            comparedTables.push(fieldVariants[field.Field])
          }
        })
        let after = '';
        if (comparedTables.length) {
          after = <div>{comparedTables.join(' &nbsp; -> &nbsp;')}</div>
        }

        return (
            <div key={table.Name.toString()}>
                <h4>{table.Name} <span className="grey">{table.Rows}</span></h4>
                <table className="optionstable">
                  <tbody>
                  <tr>
                  {Object.values(fields).map((field) => {
                    return <TableField key={field.Field} field={field} />
                  })}
                  </tr>
                  <TableData data={table.data} />
                  </tbody>
                </table>
              {after}
            </div>
        )
      });

      return (
          <div>
            {listItems}
          </div>
      );
    }
  }

  let options = <?=json_encode($pageProps)?>;
  ReactDOM.render(
      <App {...options} />,
      document.getElementById('root')
  );

</script>
