


<div id="root"></div>

<script type="text/babel">

  class App extends React.Component {

    constructor(props) {
      super(props);
      this.state = {data: false}
    }

    async loadAll() {
      let sql, mode, a;
      let data = {};
      for (let database of this.props.databases) {
        sql = `SHOW TABLE STATUS FROM ${database}`;
        mode = 'querysql'
        a = await querySql({sql, mode}, 'json')
        data [database] = a;
      }
      this.setState({data})
    }

    componentDidMount () {
      this.loadAll()
    }

    render() {
      if (!this.state.data) {
        return false;
      }

      let dbArray = {}
      let dbSimpleArray = {}
      let tablesArray = {}
      let data;
      let k = 0;
      for (let database of this.props.databases) {
        data = this.state.data[database]
        if (typeof data == 'undefined') {
          continue;
        }
        dbArray [k] = {}
        dbSimpleArray [database] = []
        for (let row of data) {
          if (typeof dbArray [database] == 'undefined') {
            dbArray [database] = []
          }
          dbArray [database][row.Name] = row;
          dbSimpleArray [database].push(row.Name)
          tablesArray [row.Name] = row.Name;
        }
        k ++
      }

      tablesArray = Object.values(tablesArray)
      tablesArray.sort()

      let params = ['Есть?', 'Рядов', 'Размер', 'Стр-ра']

      let tds = []
      let dbc = this.props.databases.count;
      for (let param of params) {
        tds.push(<td key={param} colSpan={dbc}>{param}</td>)
      }
      let tds2 = []
      for (let param of params) {
        for (let v of this.props.databases) {
          tds2.push(<td key={param+v}>{v}</td>)
        }
      }
      let customHeader = <React.Fragment>
        <tr><td rowSpan="2">&nbsp;</td><td rowSpan="2">Таблица</td>{tds}</tr>
        <tr>{tds2}</tr>
      </React.Fragment>

      //console.log(tablesArray)
      //console.log(dbSimpleArray)

      return (
        <table className="contentTable anone">
          <thead>
          {customHeader}
          </thead>
          <tbody>
          </tbody>
        </table>
      );
    }
  }

  let options = <?=json_encode($pageProps)?>;

  ReactDOM.render(
    <App {...options} />,
    document.getElementById('root')
  );


</script>
