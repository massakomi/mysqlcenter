<?php

const DIR_MYSQL = './';

require_once DIR_MYSQL . 'init.php';

$actPro = new ActionProcessor();

(new PageLayout())->display();
