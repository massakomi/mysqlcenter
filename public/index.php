<?php

const DIR_MYSQL = '../';

require_once DIR_MYSQL . 'init.php';

new ActionProcessor();

$pageProps = (new PageLayout())->execute();

$time = round(round(array_sum(explode(" ", microtime())), 10) - $msc->timer, 5);

include 'template.php';