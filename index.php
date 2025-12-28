<?php

const DIR_MYSQL = './';

require_once DIR_MYSQL . 'init.php';

$actPro = new ActionProcessor();

$pagel = new PageLayout();
$pagel->display();
