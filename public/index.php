<?php

declare(strict_types=1);

use database\Table;

const DIR_MYSQL = '../';

require_once DIR_MYSQL . 'init.php';

if (GET('test')) {
    //$dbt = new Table();
    //$dbt->copyTable('postgres', 'list', 1, 1);


    /*global $msc;
    $msc->execPdo("DROP DATABASE IF EXISTS tester_copy");

    $server = new \database\Server();
    $server->databaseCopy('tester', 'tester_copy', $isMove=0, $struct=1, $data=0);*/
}

(new controller\ActionProcessor())();

include 'template.php';