<form action="" method="post" name="formExport">
<table class="tableExport">
	<tr>
		<td valign="top"><select name="<?php echo $selectMultName?>" multiple="multiple" class="sel"><?php echo $tableSelectMult?></select><br /><a href="#" onClick="msMultiSelect('formExport', '<?php echo $selectMultName?>', 'select')" class="hs">все</a> <a href="#" onClick="msMultiSelect('formExport', '<?php echo $selectMultName?>', 'unselect')" class="hs">очистить</a> <a href="#" onClick="msMultiSelect('formExport', '<?php echo $selectMultName?>', 'invert')" class="hs">инверт</a> </td>
		<td valign="top"><?php include DIR_MYSQL . 'tpl/exportOptions.htm.php'; ?>
			WHERE условие<br />
            <input name="export_where" style="width:95%; display:block; margin:10px 0" value="<?php echo htmlspecialchars($whereCondition) ?>">
			<input type="submit" value="Экспортировать!" />
		</td>
		<td valign="top"></td>
	</tr>
</table>
</form>
<a href="<?php echo $umaker->make('s', 'exportSp')?>">Специальный экспорт</a>