
<form action="?db=<?php echo $msc->db?>" method="post" name="formTableList" id="formTableList">

<input type="hidden" name="tableMulty" value="1" />
<input type="hidden" name="action" value="" />

<?php echo $contentMain?>

<div class="chbxAction">
  <img src="<?php echo MS_DIR_IMG?>arrow_ltr.png" alt="" border="0" align="absmiddle" />
  <a href="#" onClick="chbx_action('formTableList', 'check', 'table[]'); return false" id="chooseAll">выбрать все</a>  &nbsp;  
  <a href="#" onClick="chbx_action('formTableList', 'uncheck', 'table[]'); return false">очистить</a>
</div>

<div class="imageAction">
  <u>Выбранные</u>
  <img src="<?php echo MS_DIR_IMG?>close.png" alt="" border="0" onClick="msImageAction('formTableList', 'delete_all')" />
  <img src="<?php echo MS_DIR_IMG?>delete.gif" alt="" border="0" onClick="msImageAction('formTableList', 'truncate_all')" />
  <img src="<?php echo MS_DIR_IMG?>copy.gif" alt="" border="0" onClick="msImageAction('formTableList', 'copy_all')" />
  <img src="<?php echo MS_DIR_IMG?>b_tblexport.png" alt="" border="0" onClick="msImageAction('formTableList', 'export_all', '<?php echo MS_URL?>?db=<?php echo $msc->db?>&s=export')" />

  <a href="?db=<?php echo $msc->db?>&makeMyIsam=1">Конвертировать все в MyIsam</a>
  
<select name="act" onChange="msImageAction('formTableList', this.options[this.selectedIndex].value)" >
 <option></option>
 <option value="check">проверить</option>
 <option value="analyze">анализ</option>
 <option value="optimize">оптимизировать</option>
 <option value="repair">починить</option>
 <option value="flush">сбросить кэш</option>
</select>
  
  <input type="hidden" name="copy_struct" value="1" />
  <input type="hidden" name="copy_data" value="1" />
</div>
</form>



<div style="padding:10px 0; margin-bottom:10px; font-size:14px;">
    <a href="?s=tbl_list&action=full"  title="Отобразить простую таблицу с полными данными всех таблиц, полученными с помощью запроса SHOW TABLE STATUS">Полная таблица</a>
    &nbsp;
    <a href="?s=tbl_list&action=structure"  title="">Исследование структуры таблиц</a>
</div>

<?php if (conf('showtableupdated') == '1') { ?>
<form name="form1" method="post" action="">
  Показать таблицы обновлённые с <?php echo plDrawDateSelector(strtotime(date('m/d/Y')), 'ds_')?>
  <input type="submit" value="Показать!">
</form>
<?php } ?>

