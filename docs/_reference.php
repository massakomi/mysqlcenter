<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2010
 */


/**
 * Выводит доступную информацию об указанной функции
 *
 * @param string Имя функции
 * @return array Массив различных данных о функции
 */
function _inspectFunction($functionName) {
    $prop = new ReflectionFunction($functionName);
    $function = new stdClass();
    $function->filename    = basename($prop->getFileName());
    // 2. Разбор комментария
    $co = _parseComment($prop->getDocComment());
    foreach ($co as $p => $v) {
        if ($p != 'parameters') {
            $function->$p = $v;
        }
    }
    $function->parameters  = array();
    // 3. Параметры
    foreach ($prop->getParameters() as $index => $param) {
        if ($param->isOptional()) {
            @$param->default = $param->getDefaultValue();
        }
        if (isset($co->parameters[$index])) {
            @$param->type  = $co->parameters[$index]->type;
            @$param->title = $co->parameters[$index]->title;
        }
        $function->parameters [$index]= $param;
    }
    return $function;
}

/**
 * Выводит доступную информацию об указанном классе
 *
 * @param string Имя класса
 * @return array Массив различных данных о классе
 */
function _inspectClass($className) {
    $class       = new ReflectionClass($className);
    $methods     = $class->getMethods();
    $description = preg_replace('~^[\*\s/]+~m', '', $class->getDocComment());
    foreach ($methods as $key => $method) {
        $prop = new ReflectionMethod($className, $method->name);
        unset($methods[$key]->class);
        // 1. Свойства
        if ($prop->isPublic()) {
            @$methods[$key]->type = 'public';
        }
        if ($prop->isPrivate()) {
            @$methods[$key]->type = 'private';
        }
        if ($prop->isProtected()) {
            @$methods[$key]->type = 'protected';
        }
        @$methods[$key]->static = $prop->isStatic() ? 'static' : '';
        @$methods[$key]->startLine = $prop->getStartLine();
        // 2. Разбор комментария
        $co = _parseComment($prop->getDocComment());
        foreach ($co as $p => $v) {
            if ($p != 'parameters') {
                @$methods[$key]->$p = $v;
            }
        }
        // 3. Параметры
        @$methods[$key]->parameters = array();
        foreach ($prop->getParameters() as $index => $param) {
            if ($param->isOptional()) {
                @$param->default = $param->getDefaultValue();
            }
            if (isset($co->parameters[$index])) {
                @$param->type  = $co->parameters[$index]->type;
                @$param->title = $co->parameters[$index]->title;
            }
            $methods[$key]->parameters[]= $param;
        }
    }
    return array($methods, $description);
}
/**
 * Реализует парсинг коммента вида PHPDoc
 * TODO - сейчас весь не-@ текст идёт в описание, и описание параметров может быть только однострочным. Неверно!
 *
 * @param string  коммент вида PHPDoc
 * @return object Объект с параметрами PHPDoc
 */
function _parseComment($comment) {
    $co = new stdClass();
    $co->return      = array();
    $co->description = array();
    $co->parameters  = array();
    $co->access      = '';
    $co->package     = '';
    $co->parentClass     = '';
    $commentLines    = array_map('trim', explode("\n", htmlspecialchars($comment)));
    $paramindex      = 0;
    foreach ($commentLines as $lineKey => $line) {
        if ($line == '/**' || $line == '*/') {
            continue;
        }
        // удаляем начальные "* с пробелами"
        $line = preg_replace('~^[\*\s]+~', '', $line);
        if (empty($line)) {
            continue;
        }
        // сохраняем описание по кусочкам
        if (substr($line, 0, 1) != '@') {
            $co->description []= $line;
            continue;
        }
        // оставшиеся строки вида "@ ...." делим по пробелам на 2-3 части
        $chunks = preg_split('~[\s]+~', substr($line, 1), 3);
        if (count($chunks) <= 1) {
            continue;
        }
        if (!isset($chunks[2])) {
            $chunks[2] = '';
        }
        //pre($chunks);
        // сохраняем найденные параметры в массив $userParameters
        $param = '';
        if ($chunks[0] == 'param') {
            $param = new stdClass();
            $param->type   = $chunks[1];
            $param->title  = $chunks[2];
            $co->parameters [$paramindex]= $param;
            $paramindex ++;
        } else {
            if ($chunks[0] == 'return') {
                $co->return = array($chunks[1], $chunks[2]);
            } else {
                $p = $chunks[0];
                array_shift($chunks);
                if (property_exists($co, $p)) {
                    $co->$p = trim(implode(' ', $chunks));
                }
            }
        }
    }
    $co->description = implode('<br />', $co->description);
    return $co;
}

/**
 * Выводит полную информацию о классе или о массиве функций в большой таблице
 *
 * @param mixed Имя класса, либо массив функций
 */
function _printInspectReference($inspectedData) {
    global $isInspectReferencePrinted;
    if (!isset($isInspectReferencePrinted)) {
        $isInspectReferencePrinted = true;
        ?>
        <style>
        BODY {font-family:Arial; margin:10px; font-size:100%}
        TABLE { border-collapse:collapse; empty-cells:show; width:100%}
        TD {vertical-align:top;}
        H1 {margin:10px 0; font-size:20px; background-color:#eee; padding:3px 10px}
        H2 {margin:0; font-size:18px}
        P  {font-size:14px}

        TABLE.inspect {margin-bottom:10px}
        TABLE.inspect TD, TABLE.inspect TH {border:1px solid #ccc; padding:2px 5px; font-size:12px}
        TABLE.inspect TD.name {width:100px}
        TABLE.inspect DIV.params {display:none; border:1px solid darkblue; padding:5px; margin:2px}

        TD.static {color:blue}
        TR.private TD, TR.uprivate TD {color:#ccc; font-size:10px; padding:0 5px}

        TABLE.inspect B.no {color:#aaa}
        TABLE.inspect B.integer {color:green}
        TABLE.inspect B.array {color:red}
        TABLE.inspect B.boolean {color:orange}
        TABLE.inspect B.object {color:rgb(0,153,255)}
        TABLE.inspect B.mixed {color:rgb(102,51,204)}
        TABLE.inspect B.is_null {background-color:#eee}
        </style>
        <?php
    }
    if (is_array($inspectedData)) {
        $className = 'function';
        $methods = array();
        foreach ($inspectedData as $function) {
            $method         = _inspectFunction($function);
            $method->name   = $function;
            $method->type   = '';
            $method->static = '';
            $methods      []= $method;
        }
        //pre($methods);
    } else {
        $className = $inspectedData;
        list($methods, $description) = _inspectClass($className);
        echo "<h2>$className</h2>";
        echo "<p>$description</p>";
    }
    $content = '<table class="inspect">';
    $content .= '<tr><th>Метод</th><th>Описание</th></tr>';
    foreach ($methods as $key => $mo) {
        if (isset($mo->parentClass) && $mo->parentClass != '' && $mo->parentClass != $className) {
            continue;
        }
        if ($mo->name[0] == '_' || (isset($mo->access) && $mo->access == 'private')) {
            $content .= '<tr class="uprivate '.$mo->type.'">';
        } else {
            $content .= '<tr class="'.$mo->type.'">';
        }
        $title = '';
        if (isset($mo->filename)) {
            $title = ' title="'.$mo->filename.'"';
        }
        $content .= "<td class='name $mo->static'$title>$mo->name</td>";
        $pc = array();
        foreach ($mo->parameters as $po) {
            if (!isset($po->type)) {
                @$po->type = 'no';
            }
            $nullClass = '';
            if (property_exists($po, 'default')) {
                $nullClass = ' is_null';
            }
            $str = "<b class='$po->type$nullClass' title='$po->type'>$po->name</b>";
            if (isset($po->title)) {
                $str .= "&nbsp;$po->title";
            }
            $pc []= $str;
        }
        if (!empty($mo->return)) {
            $pc []= '<b class="'.$mo->return[0].'">return</b> '.(empty($mo->return[1]) ? $mo->return[0] : $mo->return[1]);
        }
        $oncl = " onclick='document.getElementById(\"div$className$key\").style.display=\"block\"; return false'";
        $itemTitle = $mo->description;
        if (isset($mo->package)) {
            $itemTitle = ' <b>'.$mo->package.'</b> &nbsp;'.$itemTitle;
        }
        $content .= "<td$oncl class='desc'>$itemTitle&nbsp; <div id='div$className$key' class='params'>".implode('<br />', $pc)."</div></td>";
        $content .= '</tr>';
    }
    $content .= '</table>';
    echo $content;
}

chdir('..');

$classBefore = get_declared_classes();
$scripts = scandir('includes');
foreach ($scripts as $script) {
    if (substr($script, -3) == 'php') {
        include_once "includes/$script";
    }
}
$classes = array_diff(get_declared_classes(), $classBefore);

?>
<html>
<head>
  <title>Справочник</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>

<?php

echo '<h1>Справочник функций</h1>';
$functions = get_defined_functions();
//asort($functions['user']);
_printInspectReference($functions['user']);


echo '<h1>Справочник классов</h1>';
//$classes = array('Engine', 'Loader', 'File', 'MysqlQuery', 'User', '_Menu', 'Object', '_Reference');
foreach ($classes as $className) {
    if ($className == 'MySQLExport' || $className == 'Table' || $className == 'zipfile') {
        continue;
    }
    _printInspectReference($className);
}
?>
