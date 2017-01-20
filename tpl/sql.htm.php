<form method="post" enctype="multipart/form-data" name="sqlQueryForm" id="sqlQueryForm" class="tableFormEdit">
<textarea name="sql" rows="20" id="sqlContent" wrap="off"><?php echo POST('sql')?></textarea>
	<input type="submit" value="Отправить запрос!" class="submit" />	

  
<fieldset class="msGeneralForm">
<legend>Запрос из файл</legend>
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo MAX_UPLOAD_SIZE?>" /> 	<input type="file" name="sqlFile" /> <br />
	Сжатие:
	<input name="compress" type="radio" value="auto" checked="checked" />  Автодетект
	<input name="compress" type="radio" value="" />     Нет   
	<input name="compress" type="radio" value="gzip" />     gzip  
	<input name="compress" type="radio" value="zip" />     zip
	<input name="compress" type="radio" value="excel" />  excel
	<input name="compress" type="radio" value="csv" />  csv
	<br />    
	Кодировка файла: <?php echo $charsets?><br />
	(Максимальный размер: <?php echo round(MAX_UPLOAD_SIZE / (1024*1024), 2)?> Mb)	
</fieldset>
</form>


<script language="javascript">
//xla.send('db=<?=$msc->db?>&mode=sqlQuery')
</script>
