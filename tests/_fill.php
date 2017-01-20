<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */


define('DIR_MYSQL', './');
require_once DIR_MYSQL . 'config.php';

$a = $msc->query('SHOW FIELDS FROM mysqlcenter.big_table');
$fields = array();
while ($row = mysql_fetch_object($a)) {
    $fields []= $row;
}

function getRandWord() {

    $etc = explode(' ', 'the a in on may be by and to out of if with');

    $symbols = explode(' ', 'a b c d e f g h i j k l m n p q r s t u v w x y z');
    $e1 = array('ing','er','ion','or','','');
    $e2 = array('s','e','');
    
    if (rand(0,1) == 1) {
        return $etc[array_rand($etc)];
    }
    shuffle($symbols);
    $base = implode(array_slice($symbols, 0, rand(3,30)));

    return $base . $e1[array_rand($e1)] . $e2[array_rand($e2)];

    /*$a = 'абвгдеёжзийклмнопрстуфхцчшщьыъэюяqwertyuioplkjhgfdsazxcvbnm,.[]";';
    $count = rand(5,30);
    $word = '';
    for ($i = 0; $i < $count; $i ++) {
        $word .= substr($a, rand(0, 98), 1);
    }
    return $word;*/
}

/*
[Field] => contentm
[Type] => mediumtext
[Null] =>
[Key] =>
[Default] =>
[Extra] =>
*/
$insert = array();
$sql = array();
$p = '';
for ($i = 150000; $i < 150001; $i ++) {
    $data = array();
    foreach ($fields as $row) {
        $val = 1;
        if ($row->Key == 'PRI') {
            $val = $i;

        // STRING
        } else if ($row->Type == 'varchar(255)') {
            $val = getRandWord();
        } else if (strstr($row->Type, 'int')){
            $val = getRandWord();
        } else if (strstr($row->Type, 'int')){
            $val = rand(0,100);
        // DATE TIME
        } else if ($row->Type == 'date'){
            $val = date('Y-m-d', rand(100, time()));
        } else if ($row->Type == 'time'){
            $val = date('H:i:s', rand(0, 3600*24));
        } else if ($row->Type == 'datetime'){
            $val = date('Y-m-d', rand(100, time())) .' '. date('H:i:s', rand(0, 3600*24));
        } else if ($row->Type == 'timestamp'){
            $val = time();
        } else if ($row->Type == 'year(4)'){
            $val = rand(1970,2010);
        }
        $data [$row->Field]= $val;
    }
   /* if ($p == '') {
        $p = 'INSERT INTO big_table (`'.implode('`, `', array_keys($data)).'`) VALUES ("';
    }
    $sql = $p.implode('","', $data).'")';
    mysql_query($sql);
    if (mysql_error() != '') {
        $f = fopen('log.txt', 'a+');
        fwrite($f, $sql.mysql_error());
        fclose($f);
    }*/
    //echo mysql_error();
    //$sql []= implode('", "', $data);
    //$insert []= $data;
}
/*
$p = 'INSERT INTO big_table (`'.implode('`, `', array_keys($data)).'`) VALUES ("';
$sql = $p.implode('");'."\r\n".$p.'', $sql).'")';

$f = fopen('log.txt', 'w+');
fwrite($f, $sql);
fclose($f);
*/
?>



<html>
<head>
    <title>Заполнение таблицы</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>

<?php
//pre($insert);

?>