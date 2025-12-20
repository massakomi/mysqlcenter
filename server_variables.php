<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

list($vi, $vs) = getServerVersion();
$msc->pageTitle = 'Переменные сервера ('.$vi.')';

function getDataAssoc($sql, $key, $value) {
    global $msc;
    $data = $msc->fetchPdo($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
    return $data;
}

if (isajax()) {
    $sessionVars = getDataAssoc('SHOW SESSION VARIABLES', 'Variable_name', 'Value');
    $globalVars = getDataAssoc('SHOW GLOBAL VARIABLES', 'Variable_name', 'Value');
    return [
        'sessionVars' => $sessionVars,
        'globalVars' => $globalVars,
    ];
}

$this->template();
