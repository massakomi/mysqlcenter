<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

const DIR_MYSQL = './';

require_once DIR_MYSQL . 'config.php';

$pagel = new PageLayout();
$actPro = new ActionProcessor();
$umaker = new UrlMaker();

$pagel->display();













