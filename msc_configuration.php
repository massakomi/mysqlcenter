<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

if (!defined('DIR_MYSQL')) {
    exit('Hacking attempt');
}

$data = file(MS_CONFIG_FILE);
if (isajax()) {
    return compact('data');
}

$msc->pageTitle = 'Настройка MySQL Center';

$this->template([
    'data' => $data
]);