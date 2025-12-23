class Export extends React.Component {

    constructor(props) {
        super(props);
        this.state = {value: 'wait'};
    }

    msMultiSelect = (event) => {
        forElements('[name="'+this.props.selectMultName+'"] option', function(e) {
            if (event.target.classList.contains('invert')) {
                this.selected = !this.selected
            } else {
                this.selected = event.target.classList.contains('select')
            }
        })
    }

    render() {

        return (
          <form action="" method="post" name="formExport">
              <div className="tableExport">
                  <div>
                      <select name={this.props.selectMultName} multiple="multiple" className="sel" defaultValue={this.props.optionsSelected}>
                          {this.props.optionsData.map((v) =>
                            <option key={v.toString()}>{v}</option>
                          )}
                      </select><br />
                      <a href="#" onClick={this.msMultiSelect} className="hs select">все</a>
                      <a href="#" onClick={this.msMultiSelect} className="hs unselect">очистить</a>
                      <a href="#" onClick={this.msMultiSelect} className="hs invert">инверт</a>
                  </div>
                  <div>
                      <ExportOptions fields={this.props.fields} dirImage={this.props.dirImage} structChecked={this.props.structChecked} />

                      WHERE условие<br />
                      <input name="export_where" type="text" defaultValue={this.props.whereCondition} style={{width:'95%', display:'block', margin:'10px 0'}} />
                      <input type="submit" value="Экспортировать!" />
                  </div>
              </div>
              <a href={umaker({s: 'exportSp'})}>Специальный экспорт</a>
          </form>
        );
    }
}