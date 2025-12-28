function Db_compare(props) {

    const [data, setData] = React.useState(false);

    const header = (params) => {
        let cellsWithTitles = []
        let dbc = props.databases.length;
        for (let param of params) {
            cellsWithTitles.push(<td key={param} colSpan={dbc}>{param}</td>)
        }
        let cellsWithDatabases = []
        for (let param of params) {
            for (let v of props.databases) {
                cellsWithDatabases.push(<td key={param + v}>{v}</td>)
            }
        }
        return <React.Fragment>
            <tr>
                <td rowSpan="2">&nbsp;</td>
                <td rowSpan="2">Таблица</td>
                {cellsWithTitles}</tr>
            <tr>{cellsWithDatabases}</tr>
        </React.Fragment>
    }

    const getTablesArray = (dbArray) => {
        let tablesArray = {}
        for (const db in dbArray) {
            for (const table in dbArray[db]) {
                tablesArray [table]= table;
            }
        }
        tablesArray = Object.values(tablesArray)
        tablesArray.sort()
        return tablesArray
    }

    const body = (params) => {
        let dbArray = props.dbArray;
        let exportArray = props.exportArray;
        const tablesArray = getTablesArray(dbArray)
        const exportDifference = {}
        let rows = []
        for (let tableNum = 0; tableNum < tablesArray.length; tableNum++) {
            const table = tablesArray[tableNum]
            let row = []
            row.push(<input name="table[]" type="checkbox" value={table} className="cb" id={`t-${tableNum}`}/>)
            row.push(<label htmlFor={`t-${tableNum}`}>{table}</label>)
            let rowClass = '';
            let skip = false
            for (let num = 0; num < params.length; num++) {
                const param = params[num]
                let countRowsPrev = null;
                let valueSizePrev = null;
                let exportDataPrev = null;
                for (const db in dbArray) {
                    const array = Object.keys(dbArray[db])
                    let attr = {} // cell style
                    // Существует или нет - просто
                    if (num === 0) {
                        if (array.includes(table)) {
                            row.push(<a href={`/?s=tbl_data&db=${db}&table=${table}`}>есть</a>)
                        } else {
                            row.push('-')
                            rowClass = 'diff';
                        }
                    }
                    // Сравнение по количеству строк
                    if (num === 1) {
                        const countRows = dbArray[db][table] ? dbArray[db][table].Rows : '-';
                        if (countRows === countRowsPrev) {
                            attr.backgroundColor = '#ccffcc';
                        }
                        row.push({text: countRows, style: attr})
                        countRowsPrev = countRows;
                    }
                    // Сравнение по размеру данных
                    if (num === 2) {
                        if (dbArray[db][table]) {
                            let valueData = parseInt(dbArray[db][table].Data_length) + parseInt(dbArray[db][table].Index_length);
                            valueData = formatSize(valueData, 0);
                            if (valueData === valueSizePrev) {
                                attr.backgroundColor = '#99ff99';
                            }
                            row.push({text: valueData, style: attr})
                            valueSizePrev = valueData;
                        } else {
                            row.push('-')
                        }
                    }
                    // Сравнение структур из экспорта
                    if (num === 3) {
                        if (dbArray[db][table]) {
                            let exportData = exportArray[db][table]
                            if (exportData) {
                                if (exportData === exportDataPrev) {
                                    attr.backgroundColor = '#66ff66';
                                } else if (exportDataPrev !== null) {
                                    exportDifference [table] = [exportDataPrev, exportData]
                                }
                            }
                            exportDataPrev = exportData;
                            row.push({text: '+', style: attr})
                        } else {
                            row.push('-')
                        }
                    }
                }
            }

            rows.push(buildRow(row, rowClass, rows.length))
        }

        return {rows, exportDifference}
    }

    const printDifference = (exportDifference) => {
        let values = []
        for (const db in exportDifference) {
            for (const table in exportDifference[db]) {
                let data = exportDifference[db][table]
                values.push(<li>{db} {table} <pre>{data}</pre></li>)
            }
        }
        return <ul>{values}</ul>
    }

    const buildRow = (values, rowClass, rowIndex) => {
        let cells = []
        values.forEach(function (value, key) {
            let style = {}
            if (typeof value.style !== 'undefined') {
                style = value.style
                value = value.text
            }
            cells.push(<td key={`td-${rowIndex}-${key}`} style={style}>{value}</td>)
        })
        return <tr key={`row-${rowIndex}`} className={rowClass}>{cells}</tr>
    }

    let params = ['Есть?', 'Рядов', 'Размер', 'Стр-ра']
    const customHeader = header(params)
    const {rows, exportDifference} = body(params)
    return (
      <form method="post" action="/?s=tbl_compare">
          <input type="hidden" name="tableComparsion" value="1"/>
          <input type="hidden" name="databases" value={props.databases.join(',')}/>
          <input type="submit" value="Сравнить выбранные" className="submit"/>
          <table className="contentTable anone">
              <thead>
              {customHeader}
              </thead>
              <tbody>
              {rows}
              </tbody>
          </table>
          {printDifference(exportDifference)}
      </form>
    );
    
}