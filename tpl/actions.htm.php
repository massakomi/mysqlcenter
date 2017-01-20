<table width="100%"  border="0" cellspacing="0" cellpadding="3">
  <tr>
    <td>
<fieldset class="msGeneralForm">
<legend>Переименовать таблицу в:</legend>
<form action="<?php echo $DTQuery?>&action=tableRename" method="post" onSubmit="checkEmpty(this, 'newName'); return false" name="tableRename">
  <input name="newName" type="text" value="<?php echo GET('table')?>" />
  <input type="submit" value="Выполнить!" class="submit" />
</form>
</fieldset>

<fieldset class="msGeneralForm">
<legend>Переместить таблицы в (база данных.таблица):</legend>
<form action="<?php echo $DTQuery?>&action=tableMove" method="post" onSubmit="checkEmpty(this, 'newName'); return false" name="tableMove">
  <?php echo $this->getDBSelector('newDB', false, $msc->db)?>
  .
  <input name="newName" type="text" value="<?php echo GET('table')?>" />
  <input type="submit" value="Выполнить!" class="submit" />
</form>
</fieldset>

<fieldset class="msGeneralForm">
<legend>Скопировать таблицу в (база данных.таблица):</legend>
<form action="<?php echo $DTQuery?>&action=tableCopyTo" method="post" onSubmit="checkEmpty(this, 'newName'); return false" name="tableCopyTo">
  <?php echo $this->getDBSelector('newDB', false)?>
  .
  <input name="newName" type="text" value="<?php echo GET('table')?>" />
  <input type="submit" value="Выполнить!" class="submit" />
</form>
</fieldset>

<fieldset class="msGeneralForm">
<legend>Изменить кодировку таблицы</legend>
<form action="<?php echo $DTQuery?>&action=tableCharset" method="post" name="tableCharset">
   <select name="charset"><?php echo $charsetSelector ?></select>
  <input type="submit" value="Выполнить!" />
</form>
</fieldset>

<fieldset class="msGeneralForm">
<legend>Комментарий к таблице</legend>
<form action="<?php echo $umaker->make('s', 'actions', 'action', 'tableComment')?>" method="post" name="tableComment">
   <input name="comment" type="text" size="60" value="<?php echo isset($row->Comment) ? $row->Comment : '' ?>" />
  <input type="submit" value="Выполнить!" class="submit" />
</form>
</fieldset>

<fieldset class="msGeneralForm">
<legend>Изменить подрядок</legend>
<form action="<?php echo $umaker->make('s', 'actions', 'action', 'tableOrder')?>" method="post">
   <select name="field"><?php echo draw_array_options(getFields($msc->table, true))?></select>
  <select name="order"><option value="">По возрастанию</option><option value="DESC">По убыванию</option></select>
  <input type="submit" value="Выполнить!" class="submit" />
</form>
</fieldset>

<fieldset class="msGeneralForm">
<legend>Опции таблицы</legend>
<form action="<?php echo $umaker->make('s', 'actions', 'action', 'tableOptions')?>" method="post">
  <input type="checkbox" name="checksum" value="<?php echo isset($row->Checksum) ? $row->Checksum : ''  ?>" /> checksum &nbsp; &nbsp;
  <input type="checkbox" name="pack_keys" value="1" /> pack_keys <br />
  <input type="checkbox" name="delay_key_write" value="1" /> delay_key_write <br />
   <input name="auto_increment" type="text" size="3" value="<?php echo isset($row->Auto_increment) ? $row->Auto_increment : '' ?>" /> auto_increment
  <input type="submit" value="Выполнить!" class="submit" />
</form>
</fieldset>

    </td>
    <td valign="top">
<div class="globalMenu">
  <a href="<?php echo $DTQuery?>&action=tableCheck">Проверить таблицу</a> <br />
  <a href="<?php echo $DTQuery?>&action=tableAnalize">Анализ таблицы</a> <br />
  <a href="<?php echo $DTQuery?>&action=tableRepair">Починить таблицу</a> <br />
  <a href="<?php echo $DTQuery?>&action=tableOptimize">Оптимизировать таблицу</a>  <br />
  <a href="<?php echo $DTQuery?>&action=tableFlush">Сбросить кэш таблицы ("FLUSH")</a> <br />
</div>

    </td>
  </tr>
</table>
