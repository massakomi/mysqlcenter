<?php

$table = $_GET['table'];

$data = getFieldsFull($table);

// $keys = listKeys();

?>

<h3>Структура таблицы <?=$table?></h3>

<?php

printTable($data, ['cutHeaders' => 1]);
?>