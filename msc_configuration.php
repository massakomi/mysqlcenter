<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

if (!defined('DIR_MYSQL')) {
	exit('Hacking attempt');
}
//config_default.txt

if (GET('mode') == 'restore') {
	if (copy('includes/config_default.txt', MS_CONFIG_FILE)) {
        $msc->addMessage('Значения по умолчанию восстановлены');
    }
}

if (count($_POST) > 0) {

    $data = file(MS_CONFIG_FILE);
    $newFileContent = [];
    $changed = false;
    foreach ($data as $k => $line) {
    	if (empty($line) || substr_count($line, '|') < 3) {
    		continue;
    	}
    	list($name, $title, $value, $type) = explode('|', trim($line));
    	if ($type == 'boolean') {
        	if (intval(POST($name)) != intval($value)) {
                $value = intval(POST($name));
                $changed = true;
            }
        } elseif (isset($_POST[$name]) && POST($name) != $value) {
            $value = POST($name);
            $changed = true;
        }
    	$newFileContent []= "$name|$title|$value|$type";
    }
    if ($changed) {
        $f = fopen(MS_CONFIG_FILE, 'w+');
        if (fwrite($f, implode("\n", $newFileContent))) {
            $msc->addMessage('Конфиг обновлён');
        } else {
            $msc->addMessage('Не удалось записать конфиг в файл');
        }
        fclose($f);
    } else {
        $msc->addMessage('Нечего обновлять');
    }
}
$data = file(MS_CONFIG_FILE);
if (isajax()) {
    return compact('data');
}

include MS_DIR_TPL.'config.html';

$msc->pageTitle = 'Настройка MySQL Center';
