import React from 'react';

function TableField(props) {

    let field = props.field
    let style = [];
    let title = '';

    if (field.Key === 'PRI') {
        if (field.Extra !== '') {
            style.push({backgroundColor: '#FF6600'})
            title += 'priAI';
        } else {
            style.push({backgroundColor: '#FFCC00'})
            title += 'pri';
        }
    }
    if (field.Key === 'MUL') {
        style.push({backgroundColor: '#66CC99'})
        title += 'index';
    }
    if (field.Null !== 'NO') {
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

function TableData(props) {

    const strip_tags = item => {
        var div = document.createElement("div");
        div.innerHTML = item;
        return div.textContent || div.innerText || "";
    };

    return Object.values(props.data).map((items, key) => {
        return (
          <tr key={key + "index"} className="info grey">
              {Object.values(items).map((item, key) => {

                  item = strip_tags(item)

                  return <td key={key + "tds"}>{item}</td>
              })}
          </tr>
        )
    })
}

export function Tbl_struct_view(props) {

    let fieldVariants = {}
    for (const v of props.tables) {
        fieldVariants[v.Name] = v.Name
        let variant = v.Name.replace(/[s_]$/, '')
        fieldVariants[variant] = v.Name
        fieldVariants['id_'+variant] = v.Name
        fieldVariants[variant+'_id'] = v.Name
    }

    const listItems = Object.values(props.tables).map((table) => {
        let fields = table.fields

        let comparedTables = []
        Object.values(fields).map((field) => {
            if (field.Field in fieldVariants && fieldVariants[field.Field] !== table.Name) {
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
              <table className="contentTable">
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