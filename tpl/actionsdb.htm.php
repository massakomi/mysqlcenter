<fieldset class="msGeneralForm">
<legend>Переименовать базу данных в:</legend>
<form action="<?php echo $DQuery?>&action=dbRename" method="post" onSubmit="checkEmpty(this, 'newName'); return false" name="renameDBForm">
  <input name="newName" type="text" value="<?php echo GET('db')?>" />
  <input type="submit" value="Выполнить!" />
</form>
</fieldset>

<fieldset class="msGeneralForm">
<legend>Копировать базу данных в:</legend>
<form action="<?php echo $DQuery?>&action=dbCopy" method="post" onSubmit="checkEmpty(this, 'newName'); return false" name="copyDBForm">
  <input name="newName" type="text" value="<?php echo GET('db')?>" /><br>
  <input name="option" type="radio" value="struct"> Только структуру  <br>
  <input name="option" type="radio" value="all" checked> Структура и данные  <br>
  <input name="option" type="radio" value="data"> Только данные  <br>
  <!-- <input name="auto" type="checkbox" value="1" checked> Добавить значение AUTO_INCREMENT<br>
  <input name="limit" type="checkbox" value="1"> Добавить ограничения<br> -->
  <input name="switch" type="checkbox" value="1"> Перейти к скопированной БД <br><br>
  <input type="submit" value="Выполнить!" />
</form>
</fieldset>

<fieldset class="msGeneralForm">
<legend>Изменить кодировку базы данных:</legend>
<form action="<?php echo $DQuery?>&action=dbCharset" method="post" name="charsetDBForm">
  <select name="charset"><?php echo $charsetSelector ?></select>
  <input type="submit" value="Выполнить!" />
</form>
</fieldset>

<fieldset class="msGeneralForm">
<legend>Информация о базе данных</legend>
<?php echo MSC_printObjectTable($dbInfo, true) ?>
<a href="<?php echo $DQuery?>&action=fullinfo">Показать полную информацию</a>
</fieldset>