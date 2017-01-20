<html>
<head>
<title>Заголовок страницы</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>

<?php

include_once 'includes/func.php';

/**
 * Сканирует папку и возвращает список PHP файлов с полными путями
 */
function test_scandir2($dir) {
	$a = scandir($dir);
	foreach ($a as $k => $v) {
		// Пропускаем . .. .svn .htaccess и папки
		if (substr($v, 0, 1) == '.' || is_dir($dir . '/' . $v)) {
			unset($a[$k]);
			continue;
		}
		// Пропускаем не PHP файлы
		if (substr($v, -3) != 'php') {
			unset($a[$k]);
			continue;
		}
		$a [$k]= $dir != '.' ? $dir . '/' . $v : $v;
	}
	return $a;
}

/**
 * Возвращает список файлов из указанного массива папок.
 */
function test_getTestedFiles($dirs) {
	$files = array();
	foreach ($dirs as $k => $v) {
		$files = array_merge($files, test_scandir2($v));
	}	
	return $files;
}


/**
 * Тест 1
 */
function test_testHeader() {
	$files = test_getTestedFiles(array('.', 'includes'));
	foreach ($files as $k => $v) {
		$ok = strchr(file_get_contents($v), 
'/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */');
		$color = ($ok ? 'green' : 'red');
		echo '<span style="color:'.$color.'">'.$v.'</span><br>';
	}
}


/**
 * Тестовый тест, общий
 */
function test_testGeneral() {
	
}

/**
 * 
 */
function test_getFunctionUsage($functions, $pattern) {
	$files     = test_getTestedFiles(array('.', 'includes', 'tpl'));
	$functionsUsage = array();
	foreach ($files as $file) {
		$content = file_get_contents($file);
		foreach ($functions as $function) {
			if (!isset($functionsUsage [$function])) {
				$functionsUsage [$function]['total'] = 0;
				$functionsUsage [$function]['uniq']  = 0;
			}
			$pat = str_replace('{$function}', $function, $pattern);
			if (preg_match_all($pat, $content, $regs)) {
				$functionsUsage [$function]['total'] += count(isset($regs[1])?$regs[1]:$regs[0]);
				$functionsUsage [$function]['uniq']  ++;
			}
		}
	}
	return $functionsUsage;
}


/**
 * Какие у нас есть функции в коде инлюдов
 */
function test_functionUsage() {
	$classesBefore   = get_declared_classes();
	$functions = test_getMSCFunctions(array('includes'));
	$functionsUsage = test_getFunctionUsage($functions, '~(?<!function ){$function}\s*\(~im');
	arsort($functionsUsage);
	$t = new Table();
	$t->makeRowHead('', 'Всего', 'Файлы');
	foreach ($functionsUsage as $k => $v) {
		$t->makeRow(array_merge(array($k), $v));
	}
	echo $t->make();
	echo 'Всего - во всех местах всех файлов. Файлы - кол-во уникальных файлов, где используется';

	$classesAfter = get_declared_classes();
	$classes      = array_diff($classesAfter, $classesBefore);
	$allMethods = array();
	foreach ($classes as $class) {
		$allMethods = array_merge($allMethods, get_class_methods($class));
	}
	$allMethods = array_unique($allMethods);
	$mu = test_getFunctionUsage($allMethods, '~->\s*{$function}~im');
	$mu2 = test_getFunctionUsage($allMethods, '~::\s*{$function}~im');
	$t = new Table();
	$t->makeRowHead('Класс', 'Метод', 'Всего', 'Файлы');
	foreach ($classes as $class) {
		$methods = get_class_methods($class);
		$method  = array_shift($methods);
		$t->makeRow($class.' ('.count($methods).')', $method, $mu[$method]['total']+$mu2[$method]['total'], $mu[$method]['uniq']+$mu2[$method]['uniq']);
		foreach ($methods as $method) {
			$t->makeRow('&nbsp;',                    $method, $mu[$method]['total']+$mu2[$method]['total'], $mu[$method]['uniq']+$mu2[$method]['uniq']);
		}
	}
	echo $t->make();
	
}
function test_getMSCFunctions($dirs) {
	$files = test_getTestedFiles($dirs);
	foreach ($files as $k => $v) {
		include_once $v;
	}
	$funcs = get_defined_functions();
	foreach ($funcs['user'] as $k => $v) {
		if (substr($v, 0, 5) == 'test_') {
			unset($funcs['user'][$k]);
			continue;
		}
	}	
	return $funcs['user'];
}




// Список тестов и их заголовков

$tests = array(
	'test_testHeader'    => 'Тест наличия строки заголовка',
	'test_testGeneral'   => 'Общий тест',
	'test_functionUsage' => 'Тест использования фукнций',
);





// Выполнение тестов


foreach ($tests as $k => $v) {
	echo '<a href="?'.$k.'=1">'.$v.'</a> &nbsp; ';
}
foreach ($tests as $k => $v) {
	if (isset($_GET[$k])) {
		echo '<h2>'.$v.'</h2>';
		call_user_func($k);
	}
}




?>
</body>
</html>