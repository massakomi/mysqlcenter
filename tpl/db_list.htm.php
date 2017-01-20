<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr>
    <td valign="top">
<form action="?s=db_list" method="post" name="formDatabases" id="formDatabases">
  <input type="hidden" name="dbMulty" value="1" />
  <input type="hidden" name="action" value="" />
  <?php echo $table->make() ?>
</form>
<div class="chbxAction">
  <img src="<?php echo MS_DIR_IMG ?>arrow_ltr.png" alt="" border="0" align="absmiddle" />
  <a href="#" onClick="chbx_action('formDatabases', 'check', 'databases[]'); return false">выбрать все</a>  &nbsp;
  <a href="#" onClick="chbx_action('formDatabases', 'uncheck', 'databases[]'); return false">очистить</a>
</div>
	</td>
    <td valign="top">
<fieldset class="msGeneralForm">
<legend>Создание базы данных</legend>
<form action="?s=tbl_list&action=dbCreate" method="post">
  <input name="dbName" type="text" value="" />
  <input type="submit" value="Создать!">
</form>
</fieldset>
<?php
if (!$showFullInfo) {
	echo '    <a href="'.$umaker->make('mode', 'full').'" title="Сканирует все таблицы всех баз данных и выводит количество таблиц, размер, дату обновления и количество рядов">Показать полную таблицу</a>';
} else {
	echo '    <a href="'.$umaker->make('mode', '').'">Показать краткую таблицу</a>';
}
	echo '<br><a href="'.$umaker->make('mode', 'speedtest').'">Тест скорости</a>';
?>

<br />
<br />

<?php echo MS_APP_NAME ?><br />
Версия <?php echo MS_APP_VERSION ?><br />
Хост: <?php echo DB_HOST ?><br /><br />

Версия сервера: <?php echo PMA_MYSQL_STR_VERSION ?><br />
Версия PHP: <?php echo  phpversion() ?><br />
БД: <?php echo $msc->db ?><br />

<fieldset class="msGeneralForm">
<legend>Добавить пользователя</legend>
<form action="?s=users&action=add" method="post">
  <div style="margin-bottom:5px"><input name="rootpass" type="text" value="" /> Пароль админа</div>
  <div style="margin-bottom:5px"><input name="database" type="text" value="" id="databaseField" onkeyup="updateButton(); $('unameField').value=this.value" /> Имя базы данных</div>
  <div style="margin-bottom:5px"><input name="databaseuser" onkeyup="updateButton()" id="unameField" type="text" value="" /> Логин пользователя</div>
  <div style="margin-bottom:5px"><input name="userpass" type="password" id="passwordField" value="" onkeyup="updateButton()" /> Пароль</div>
  <div style="margin-bottom:5px"><input name="userpass2" type="password" id="password2Field" value="" onkeyup="updateButton()" /> Пароль еще раз</div>
  <div><input type="submit" value="Добавить" disabled="true" id="submitBtnId" /></div>
</form>
</fieldset>

<script language="javascript">
function updateButton() {
  var doEnable = true;
  if ($('databaseField').value == '') {
  	doEnable = false;
  }
  if ($('unameField').value == '') {
  	doEnable = false;
  }
  if ($('passwordField').value == '' || ($('passwordField').value != $('password2Field').value)) {
  	doEnable = false;
  }
  if (doEnable) {
    $('submitBtnId').disabled = false;
  }
}
</script>

	</td>
  </tr>
</table>

<div class="imageAction">
  <u>Выбранные</u>
  <input type="image" src="<?php echo MS_DIR_IMG?>close.png" title="Удалить базы данных" onClick="msImageAction('formDatabases', 'dbDelete'); return false" />
  <input type="image" src="<?php echo MS_DIR_IMG?>copy.gif" title="Скопировать базы данных по шаблону {db_name}_copy" onClick="msImageAction('formDatabases', 'dbCopy'); return false" />
  <input type="image" src="<?php echo MS_DIR_IMG?>b_tblexport.png" title="Перейти к экспорту баз данных" onClick="msImageAction('formDatabases', 'exportDatabases', '<?php echo MS_URL?>?s=export'); return false" />
  <input type="image" src="<?php echo MS_DIR_IMG?>fixed.gif" title="Сравнить выбранные базы данных" onClick="msImageAction('formDatabases', '', '<?php echo MS_URL?>?s=db_compare'); return false" />
</div>