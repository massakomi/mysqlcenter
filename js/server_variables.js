
class serverVarsPage {

  getAllVars() {
    let sql = 'SHOW SESSION VARIABLES';
    let mode = 'querysql'
    let type = 'pair-value'
    let qs = querySql({sql, mode, type})
        .then((text) => {
          this.sessionVars = JSON.parse(text)
        })
        .then(this.getGlobals.bind(this));
    return qs;
  }

  getGlobals() {
    let sql = 'SHOW GLOBAL VARIABLES';
    let mode = 'querysql'
    let type = 'pair-value'
    let qs = querySql({sql, mode, type})
        .then((text) => {
          this.globalVars = JSON.parse(text)
        });
    return qs;
  }
}

const wrap = (s, cmp) => {
  if (s == undefined || cmp == s) {
  	return null
  } else {
    return <span title={s}>{s.substr(0, 20)}</span>
  }
}

function App(props) {

  let trs = []
  let i =0;
  for (let prop in props.sessionVars) {
    i ++
    trs.push((
        <tr key={i}>
          <td><b>{prop.replace('_', ' ')}</b></td>
          <td>{wrap(props.sessionVars[prop])}</td>
          <td>{wrap(props.globalVars[prop], props.sessionVars[prop])}</td>
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
  )
}


let page = new serverVarsPage()
page.getAllVars().then(() => {
  ReactDOM.render(
    <App globalVars={page.globalVars}  sessionVars={page.sessionVars} />,
      document.getElementById('root')
  );
})

