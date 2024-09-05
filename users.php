<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */
	

$msc->pageTitle = 'Пользователи баз данных';

$rootpass  = $_POST['rootpass']; // ?

$username  = $_POST['databaseuser'];
$database  = $_POST['database'];
$userpass  = $_POST['userpass'];

/*
$result = $msc->query('DROP USER massakomi');
var_dump($result);
*/

if ($rootpass == '') {
  $msc->addMessage('Пароль админа пустой', '', MS_MSG_NOTICE);
}

// Проверяем, может уже есть такой пользователь
$sql = 'SELECT * FROM mysql.user WHERE User="'.$username.'"';
$result = $msc->query($sql);
if ($result) {
  $msc->addMessage('Пользователь с именем "'.$username.'" уже существует', '', MS_MSG_NOTICE);
  return;
}


// Сначала добавляем пользователя
$sql = 'CREATE USER `'.$username.'` IDENTIFIED BY "'.$userpass.'"';
$result = $msc->query($sql);
if ($result) {
  $msc->addMessage('Пользователь "'.$username.'" добавлен', $sql, MS_MSG_SUCCESS);
} else {
  $msc->addMessage('Ошибка добавления пользователя "'.$username.'"', $sql, MS_MSG_FAULT);
  return;
}

// Теперь добавляем базу данных
$sql = 'CREATE DATABASE `'.$database.'`';
$result = $msc->query($sql);
if ($result) {
  $msc->addMessage('База данных "'.$database.'" создана', $sql, MS_MSG_SUCCESS);
} else {
  $msc->addMessage('Ошибка создания базы данных "'.$database.'"', $sql, MS_MSG_FAULT);
  return;
}

// Теперь наделяем привелегиями пользователя на эту базу
$sql = 'GRANT ALL ON `'.$database.'`.* TO `'.$username.'`';
$result = $msc->query($sql);
if ($result) {
  $msc->addMessage('Права на базу "'.$database.'" отданы пользоватлю "'.$username.'"', $sql, MS_MSG_SUCCESS);
} else {
  $msc->addMessage('Ошибка наделения прав на базу "'.$database.'"', $sql, MS_MSG_FAULT);
  return;
}