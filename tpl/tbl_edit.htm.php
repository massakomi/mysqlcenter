<?php
if (POST('action') != 'fieldsAdd'&&!isset($_GET['field'])&&$_POST['action'] != 'fieldsEdit') { ?>
<form method="post" action="" class="tableFormEdit" name="addForm" onSubmit="checkEmpty(this, 'table_name'); return false">
  <input tabindex="1" type="text" name="table_name" size="40" value="<?php echo POST('tableName')?>" /> имя таблицы <br /> <?php } else { ?>

<form method="post" action="" class="tableFormEdit" name="addForm">
  <input type="hidden" name="afterSql" value="<?php echo isset($afterSql)?$afterSql:''?>"> <?php } ?>

  <input type="hidden" name="action" value="<?php echo GET('s')=='tbl_add'&&empty($_POST)&&!isset($_GET['field'])?'tableAddEnd':(POST('action') == 'fieldsAdd'?'fieldsAddEnd':'fieldsEditEnd')?>">
  <img src="<?php echo MS_DIR_IMG?>nolines_plus.gif" alt="" border="0"
    onClick="addDataRow('tableFormEdit'); return false" title="Добавить поле" style="cursor:pointer " />
  <img src="<?php echo MS_DIR_IMG?>nolines_minus.gif" alt="" border="0"
    onClick="removeRow('tableFormEdit', 'end'); return false"  title="Удалить поле" style="cursor:pointer "/><br />
  <?php echo $cont?>
  <input tabindex="100" type="submit" value="Выполнить!" class="submit" />
</form>
<script language="JavaScript" type="text/javascript">


/**
 * Специальная функция для изменения параметров скопированного ряда. Сначала копируется ряд.
 * Далее меняются индексы у аттрибутов name, если требуется. Ид и прочие аттрибуты не трогаются пока.
 */
function addDataRow(id) {
	var newTR = addRow(id);
	var inputs = newTR.getElementsByTagName('INPUT')
	for (var i = 0; i < inputs.length; i++) {
        var res = /([a-z]+)\[(\d+)\]/i.exec(inputs[i].name)
        if (res != null) {
            var nextName = res[1] + '['+ (Number(res[2]) + 1) +']';
            inputs[i].name = nextName;
        }
  	}
}

// В зависимости от выбранного типа поля можно что-то изменить в других полях строки
jQuery('//input@[name="ftype[]"]').change(function() {
    var curType = jQuery(this).val();
    if (curType == 'SERIAL') {
        var autoinc = jQuery(this).parent().parent().find('input:eq(5)');
    	autoinc.attr('checked', true);
    	autoinc.attr('disabled', true);
        var nulled = jQuery(this).parent().parent().find('input:eq(3)');
    	nulled.attr('disabled', true);
    }
    if (curType == 'ENUM' || urType == 'SET') {
        var value = jQuery(this).parent().parent().find('input:eq(2)');
        value.val("'','',''");
    }
});

if (document.forms['addForm'].table_name) {
    document.forms['addForm'].table_name.focus();
}
</script>