
<b>1. Выберите таблицу, в которую импортируются данные</b><br>
<?php echo $tables_rows?>

<h3>2. Выберите файл с данными</h3>
<form name="form1" enctype="multipart/form-data" method="post" action="">

загрузить с компьютера <input type="file" name="file"> или <br><br>


указать путь к файлу <input type="text" name="textfield"><br><br>

<input type="submit" value="Изменить!">
</form>


<h3>3. Настройка параметров импорта</h3>

<form name="form1" method="post" action="">

разделитель <input type="text" name="textfield"><br><br>

<input type="submit" value="Импортировать!">
</form>
