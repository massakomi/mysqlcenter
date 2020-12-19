<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top">
<form action="<?php echo $umaker->make('s', 'tbl_add')?>" method="post" name="formTableStructure" id="formTableStructure">
<input type="hidden" name="action" value="" />

<?php echo $contentMain?>

<div class="chbxAction">
  <img src="<?php echo MS_DIR_IMG ?>arrow_ltr.png" alt="" border="0" align="absmiddle" />
  <a href="#" onClick="chbx_action('formTableStructure', 'check', 'field[]'); return false">выбрать все</a>  &nbsp;  
  <a href="#" onClick="chbx_action('formTableStructure', 'uncheck', 'field[]'); return false">очистить</a>
</div>

<div class="imageAction">  
  <u>Выбранные</u>
  <input type="image" src="<?php echo MS_DIR_IMG?>edit.gif" onClick="msImageAction('formTableStructure', 'fieldsEdit')" />
  <input type="image" src="<?php echo MS_DIR_IMG?>close.png" onClick="msImageAction('formTableStructure', 'fieldsDelete'); return false" />
</div>
</form>

<a href="<?php echo $umaker->make('s', 'tbl_struct', 'print', '1')?>">Печатная версия</a>	
<fieldset class="msGeneralForm">
<legend>Изменить структуру</legend>
<form action="<?php echo $umaker->make('s', 'tbl_add')?>" method="post" onSubmit="checkEmpty(this, 'fieldsNum'); return false">
  <input type="hidden" name="action" value="fieldsAdd" />
  Добавить полей &nbsp; <input name="fieldsNum" type="text" value="1" size="5" /> &nbsp;
  <input name="afterOption" type="radio" value="end" checked id="f1"> <label for="f1">в конец </label>
  <input name="afterOption" type="radio" value="start" id="f2"> <label for="f2">в начало</label>
  <input name="afterOption" type="radio" value="field" id="f3">  <label for="f3">после </label>
  <select name="afterField" onFocus="get('f3').checked = true"><?php echo draw_array_options($fieldNames)?></select>
  <input type="submit" value="Добавить!">
</form>
</fieldset>
	</td>
    <td valign="top" style="padding:0 0 0 10px">
<strong>Подробности таблицы</strong><br /><?php echo $dbt->insertDetailsTable()?>
	</td>
  </tr>
</table>

<strong>Информация о ключах</strong>
<img src="<?php echo MS_DIR_IMG?>i-help2.gif" title="Индексы - это сбалансированные деревья значений указанных в индексе полей и ссылки на физические записи в таблице. Индексы позволяют ускорить работу выполнения запросов в сотни раз и сразу находить нужные данные, вместо того, чтобы последовательно читать всю таблицу." alt="" border="0" align="absmiddle" class="helpimg" /><br />


<?php
if ($msc->table != '') {
    $res = $msc->query('SHOW KEYS FROM `'.$msc->table.'`');
    $table = new Table('contentTable');
    $table->makeRowHead(
    	'',
    	'<span title="Имя таблицы">Таблица</span>',
    	'<span title="0 - уникальные значения, 1 - не уникальные">Не уникальное</span>',
    	'<span title="Имя ключа">Ключ</span>',
    	'<span title="Порядковый номер ключа, начиная с 1">Номер</span>',
    	'<span title="Имя колонки (поля)">Колонка</span>',
    	'<span title="Сортировка колонки в ключе. В MySQL, значение ‘A’ (по возрастанию) или NULL (без сортировки)">Сортировка</span>',
    	'<span title="Приблизительное число уникальных значений в индексе. Это поле обновляется при запуске  ANALYZE TABLE или myisamchk -a. Cardinality расчитывается на основе цифровой статистики, поэтому его значение не обязательно будет точным даже для небольших таблиц. Чем выше cardinality, тем больше шансов, что MySQL будет применять индекс в операциях объединения (JOIN)">Cardinality</span>',
    	'<span title="Количество индексированных символов, если колонка только частично индексирована, NULL если вся колонка индексирована">Sub_part</span>',
    	'<span title="Как упакован ключ. NULL если не упакован. Хранение значений в сжатом (упакованном) виде используется, если в индексе присутствуют поля, у которых переменная длина">Packed</span>',
    	'<span title="YES если колонка может содержать NULL. Если нет, то поле содержит NO после MySQL 5.0.3, и \'\' в предыдущих версиях">Null</span>',
    	'<span title="Метод индексирования (BTREE - если длина полей индекса не превышает 10 байт, HASH - хранение значений как хэш кодов. Используется, если индекс составной, его длина больше одной восьмой от размера страницы БД или же больше, чем 256 байт, FULLTEXT, RTREE)">Тип индекса</span>',
    	'<span title="">Комментарий</span>'
    );
    while ($row = mysqli_fetch_assoc($res)) {
        array_unshift($row, '<a href="'.$umaker->make('s', 'tbl_struct', 'key', $row['Key_name'], 'field', $row['Column_name'], 'action', 'deleteKey').'" onclick="check(this, \'удаление ключа\'); return false"><img src="'.MS_DIR_IMG.'close.png" alt="" border="0" /></a>');
    	 $table->makeRow($row);
    }
    echo $table->make();
}
?>


<p><a href="<?php echo $umaker->make('s', 'tbl_struct', 'action', 'add_key') ?>">Добавить ключ</a></p>

<?php
echo '<div style="color:#666; border:1px solid #ccc; padding:10px;">'.implode('<br />', $tableAddStr).'</div>';
?>