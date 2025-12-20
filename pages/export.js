class Export extends React.Component {

    constructor(props) {
        super(props);
        this.state = {value: 'wait'};
    }

    msMultiSelect = (event) => {
        if (event.target.classList.contains('invert')) {
            $('[name="'+this.props.selectMultName+'"] option').prop('selected', function() {
                return !this.selected
            })
        } else {
            $('[name="'+this.props.selectMultName+'"] option').prop('selected', event.target.classList.contains('select'))
        }
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