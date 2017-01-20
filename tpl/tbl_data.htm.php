<style>
/* Страницы-ссылки для tbl_data */
DIV.contentPageLinks { margin:5px 0 0;}
DIV.contentPageLinks a { text-decoration:none; font-size:10px; margin: 0; padding: 2px 5px; border:1px solid #eee }
DIV.contentPageLinks a.cur { background-color: #0000FF; color:#FFFFFF}
/* Селектор для tbl_data */
SELECT.miniSelector { font-size:12px;}
SELECT.miniSelector OPTION { font-size:12px;}
</style>
<form action="<?php echo $umaker->make('s', 'tbl_data')?>" method="post" name="formTableRows" id="formTableRows">
<input type="hidden" name="rowMulty" value="1" />
<input type="hidden" name="action" value="" />
<?php echo $links?>
<?php echo $table->make()?>
<?php echo $links?>
<div class="chbxAction">
  <img src="<?php echo $p?>arrow_ltr.png" alt="" border="0" align="absmiddle" />
  <a href="#" onClick="chbx_action('formTableRows', 'check', 'row[]'); return false">выбрать все</a>  &nbsp;
  <a href="#" onClick="chbx_action('formTableRows', 'uncheck', 'row[]'); return false">очистить</a>
</div>

<div class="imageAction">
 <table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
	 <u>Выбранные</u>
  <img src="<?php echo MS_DIR_IMG?>edit.gif" alt="" border="0" onClick="msImageAction('formTableRows', 'editRows', '<?php echo $umaker->make('s', 'tbl_change')?>')" />
  <img src="<?php echo MS_DIR_IMG?>close.png" alt="" border="0" onClick="msImageAction('formTableRows', 'deleteRows')" />
  <img src="<?php echo MS_DIR_IMG?>copy.gif" alt="" border="0" onClick="msImageAction('formTableRows', 'copyRows')" />
  <img src="<?php echo MS_DIR_IMG?>b_tblexport.png" alt="" border="0" onClick="msImageAction('formTableRows', 'exportRows', '<?php echo $umaker->make('s', 'export')?>')" />
	</td>
    <td align="right" id="tblDataInfoId">&nbsp;</td>
  </tr>
</table>
</div>
</form>

<?php if (conf('showtablecompare') == '1') { ?>
<form name="form1" method="post" action="<?php echo $umaker->make('s', 'tbl_compare')?>">
	<input type="hidden" name="table[]" value="<?php echo $msc->table?>">
	Сравнить таблицу с такой же в <?php echo $this->getDBSelector('database', false)?>
  <input type="submit" value="Сравнить">
</form>
<?php } ?>
<script language="javascript">

jQuery('.contentTable TD').click(function(){
    var tr = jQuery(this).parent();
    var ch = tr.find('input').attr('checked');
    if (jQuery(this).index() == 0) {
    	if (ch) {
            tr.addClass('selectedRow');
    	} else {
    		tr.removeClass('selectedRow');
    	}
    	return true;
    }
	if (!ch) {
        tr.addClass('selectedRow');
	} else {
		tr.removeClass('selectedRow');
	}
    jQuery(this).parent().find('input').attr('checked', !ch)
});

jQuery('.contentTable TR').dblclick(function(){
    location.href = jQuery(this).find('a').attr('href');
});

jQuery('.contentTable TD').click(function(){
    if (jQuery(this).index() <= 2) {
        return true;
    }
    if (globalCtrlKeyMode) {
    	jQuery(this).html('<input type="text" value="'+jQuery(this).html()+'" id="editable">');
    	jQuery('#editable').focus();
    	jQuery('#editable').focusout(function() {
            var html = jQuery(this).val();
            jQuery(this).parent().html(html);
        });
    }
    return true;
});

</script>
