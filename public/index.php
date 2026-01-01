<?php

declare(strict_types=1);
const DIR_MYSQL = '../';

require_once DIR_MYSQL . 'init.php';

(new controller\ActionProcessor())();

include 'template.php';