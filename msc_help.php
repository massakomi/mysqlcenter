<div id="root"></div>

<script type="text/javascript">

  async function printTable(sql) {
    let data = await querySql({sql, mode: 'querysql'}, 'json')
    let output = '<table class="contentTable">'
    let headersPrinted = false
    for (let item of data) {
      if (!headersPrinted) {
        output += '<tr>'
        for (let column of Object.keys(item)) {
          output += `<th>${column}</th>`
        }
        output += '</tr>'
        headersPrinted = 1
      }
      output += '<tr>'
      for (let column of Object.keys(item)) {
        let value = item[column]
        if (column == 'name') {
          value = `<a href="?s=msc_help&id=${item.help_category_id}">${item.name}</a>`
        }
        output += `<td>${value}</td>`
      }
    }
    output += '</table>'
    $('#root').html(output)
  }


    let id = new URLSearchParams(window.location.search).get('id');
    let sql = '';
    if (id) {
      sql = `SELECT * FROM mysql.help_topic WHERE help_category_id = ${id}`
    } else {
      sql = 'SELECT * FROM mysql.help_category ORDER BY name';
    }

  printTable(sql)

</script>
