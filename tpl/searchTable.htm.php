<fieldset class="msGeneralForm">
<legend>Добавить к условию WHERE</legend>
<form action="<?php echo $umaker->make('s', 'tbl_data')?>" method="get" onsubmit="sendGet(this); return false">
  <input name="where" type="text" value="" style="width:95%" id="where" />
  <input type="submit" value="Выполнить!" class="submit" style="display:inline" />
  вставить
  <?php

  $fields = array_map(create_function('$a', 'return $a;'), $fields);
  array_unshift($fields, '[поля]');
  echo plDrawSelector($fields, ' onchange="$(\'where\').value += this.options[this.selectedIndex].text"');
  
  echo plDrawSelector(array(
    '[операнды]', ' = ', ' != ', ' < ', ' > ', 'IS NULL',
    ' LIKE "%%" ', ' LIKE "%" ', ' LIKE "" ', ' NOT LIKE "" ',
    ' REGEXP "^fo" '
    ),
    ' onchange="$(\'where\').value += this.options[this.selectedIndex].text"');
    
    
  echo plDrawSelector(array(
    '[функции]', 'UPPER()', 'LOWER()', 'TRIM()', 'SUBSTRING()', 'REPLACE()', 'REPEAT()'
    ),
    ' onchange="$(\'where\').value += this.options[this.selectedIndex].text"');
    
  ?>
  
</form>
</fieldset>

<fieldset class="msGeneralForm">
<legend>Найти и заменить</legend>
<form action="" method="post">
<table width="100%"  border="0" cellspacing="0" cellpadding="3">
  <tr>
    <td width="100">Найти</td>
		<td><input name="search_for" type="text" style="width:95% " value="<?php echo POST('search_for')?>" /></td>
  </tr>
  <tr>
    <td width="100">Заменить</td>
		<td><input name="replace_in" type="text" style="width:95% " value="<?php echo POST('replace_in')?>" /></td>
  </tr>
  <tr>
    <td width="100">Поле</td>
		<td><?php echo $fieldSelector ?></td>
  </tr>	
</table>
  <input type="submit" value="Выполнить" class="submit" />
</form>
</fieldset>


<script language="javascript">
function sendGet(obj) {
    window.location = obj.action + '&where=' + obj.elements[0].value;
}
</script>