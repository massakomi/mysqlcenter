<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

list($vi, $vs) = getServerVersion();
$msc->pageTitle = 'Переменные сервера ('.$vi.')';
?>


<div id="root"></div>

<!-- Load React. -->
<!-- Note: when deploying, replace "development.js" with "production.min.js". -->
<script src="https://unpkg.com/react@16/umd/react.development.js" crossorigin></script>
<script src="https://unpkg.com/react-dom@16/umd/react-dom.development.js" crossorigin></script>
<script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>

<script type="text/babel">

const wrap = (s, cmp) => {
  if (s === undefined || cmp === s) {
    return null
  } else {
    return <span title={s}>{s.substr(0, 20)}</span>
  }

}

class App extends React.Component {

  constructor(props) {
    super(props);
    this.state = {sessionVars: [], globalVars: []}
  }

  async loadAll() {
    let sql = 'SHOW SESSION VARIABLES';
    let mode = 'querysql'
    let type = 'pair-value'
    let sessionVars = await querySql({sql, mode, type}, 'json')
    //console.log(sessionVars)
    sql = 'SHOW GLOBAL VARIABLES';
    mode = 'querysql'
    type = 'pair-value'
    let globalVars = await querySql({sql, mode, type}, 'json')
    this.setState({globalVars, sessionVars})
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
          <td>{wrap(this.state.sessionVars[prop])}</td>
          <td>{wrap(this.state.globalVars[prop], this.state.sessionVars[prop])}</td>
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

ReactDOM.render(
  <App />,
    document.getElementById('root')
);


</script>