<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */

$msc->pageTitle = 'Различная информация';


$res = $msc->query('SHOW COUNT(*) ERRORS');
while ($row = mysqli_fetch_assoc($res)) {
	pre($row);
}


echo '<h3>Пользователи</h3>';
$res = $msc->query('SELECT * FROM mysql.user');
$table = new Table('contentTable');
$data = array();
while ($row = mysqli_fetch_assoc($res)) {
	foreach ($row as $k => $v) {
		if (!isset($data [$k])) {
			$data [$k][]=$k;
		}
		$data [$k][]= $v;
	}
}

foreach ($data as $k => $row) {
	 if ($row[0] == 'User') {
	 	$table->makeRowHead($row);
		continue;
	 }
	 $table->makeRow($row);	
}
echo $table -> make();



echo '<h3>SHOW GRANTS</h3>';
$res = $msc->query('SHOW GRANTS');
$table = new Table('contentTable');
while ($row = mysqli_fetch_assoc($res)) {
	 if ($table->tableCont == null) {
	 	$table->makeRowHead(array_keys($row));
	 }
	 $table->makeRow($row);
}
echo 'Список привилегий, предоставленных аккаунту, который вы используете для соединения с сервером (FOR CURRENT_USER)';
echo $table -> make(); 

/*
This statement lists the GRANT statement or statements that must be issued to duplicate the privileges that are granted to a MySQL user account. The account is named using the same format as for the GRANT statement; for example, 'jeffrey'@'localhost'. If you specify only the username part of the account name, a hostname part of '%' is used. For additional information about specifying account names, see Section 13.5.1.3, “GRANT Syntax”. 

mysql> SHOW GRANTS FOR 'root'@'localhost';
+---------------------------------------------------------------------+
| Grants for root@localhost                                           |
+---------------------------------------------------------------------+
| GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION |
+---------------------------------------------------------------------+

To list the privileges granted to the account that you are using to connect to the server, you can use any of the following statements: 

SHOW GRANTS;
SHOW GRANTS FOR CURRENT_USER;
SHOW GRANTS FOR CURRENT_USER();

As of MySQL 5.0.24, if SHOW GRANTS FOR CURRENT_USER (or any of the equivalent syntaxes) is used in DEFINER context, such as within a stored procedure that is defined with SQL SECURITY DEFINER), the grants displayed are those of the definer and not the invoker. 

SHOW GRANTS displays only the privileges granted explicitly to the named account. Other privileges might be available to the account, but they are not displayed. For example, if an anonymous account exists, the named account might be able to use its privileges, but SHOW GRANTS will not display them. 

*/





echo '<h3>SHOW PRIVILEGES</h3>';
$res = $msc->query('SHOW PRIVILEGES');
$table = new Table('contentTable');
while ($row = mysqli_fetch_assoc($res)) {
	 if ($table->tableCont == null) {
	 	$table->makeRowHead(array_keys($row));
	 }
	 $table->makeRow($row);
}
echo 'Список системных привилегий, которые поддерживает MySQL сервер. Точный список привилегий зависит от версии вашего сервера.';
echo $table -> make(); 



echo '<h3>SHOW ENGINES</h3>';
$res = $msc->query('SHOW ENGINES');
$table = new Table('contentTable');
while ($row = mysqli_fetch_assoc($res)) {
	 if ($table->tableCont == null) {
	 	$table->makeRowHead(array_keys($row));
	 }
	 $table->makeRow($row);
}
echo 'SHOW ENGINES displays status information about the server\'s storage engines. This is particularly useful for checking whether a storage engine is supported, or to see what the default engine is.';
echo $table -> make();




/*
GRANT priv_type [(column_list)] [, priv_type [(column_list)]] ...
    ON [object_type] {tbl_name | * | *.* | db_name.*}
    TO user [IDENTIFIED BY [PASSWORD] 'password']
        [, user [IDENTIFIED BY [PASSWORD] 'password']] ...
    [REQUIRE
        NONE |
        [{SSL| X509}]
        [CIPHER 'cipher' [AND]]
        [ISSUER 'issuer' [AND]]
        [SUBJECT 'subject']]
    [WITH with_option [with_option] ...]

object_type =
    TABLE
  | FUNCTION
  | PROCEDURE

with_option =
    GRANT OPTION
  | MAX_QUERIES_PER_HOUR count
  | MAX_UPDATES_PER_HOUR count
  | MAX_CONNECTIONS_PER_HOUR count
  | MAX_USER_CONNECTIONS count


*/



$res = $msc->query("
GRANT ALL
    ON *
    TO zorro IDENTIFIED BY 'zorro'
");
echo mysqli_error();



?>