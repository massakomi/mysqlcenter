<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

if (GET('id') == '') {			
	$result = $msc->query('SELECT * FROM mysql.help_category ORDER BY name');
    $data = [];
    while ($o = mysqli_fetch_object($result)) {
        $data []= $o;
    }
} else {

	$result = $msc->query('SELECT * FROM mysql.help_topic WHERE help_category_id = ' . GET('id'));
	$data = [];
    while ($o = mysqli_fetch_object($result)) {
        $data []= $o;
    }
}
?>

<script type="text/javascript">

    /*class PrintTable {
      constructor() {

      }
      get test() {
        return 1
      }
    }


    table = new PrintTable()
    console.log(table.test)*/

  function printTable(data) {
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
    document.write(output)
  }

  let data = <?=json_encode($data)?>;
  printTable(data)




</script>
