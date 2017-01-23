<?php




function msg()
{
?>

<?php

}

function err($e)
{
    echo '<div class="alert alert-danger" style="margin-top:15px;">'.$e.'</div>';
}



function pagination($limit, $countAll, &$offset, $var='start', $urlTmpl='', $from=1, $tag='a') {
    $pageLinks = '<ul class="pagination pagination-sm">';
    $pageCount = ceil($countAll / $limit);
    $start = $_GET[$var] ? intval($_GET[$var]) : 1;
    $offset = ($start - 1) * $limit;
    if ($pageCount == 1) {
        return '';
    }
    $j = 0;
    $floatLimit = 10;
    if ($start > $floatLimit) {
        $pageLinks .= '<li><a href="'.url($var.'=1').'">1...</a></li> ';
    }
    $pageCount += $from;
    for ($i = max($from, $start - $floatLimit); $i < $pageCount; $i ++) {
        if ($j > $floatLimit * 2) {
            break;
        }
        $st = '';
        // echo '<br />- '."$i == $start";
        if ($i == $start) {
            $st = ' class="active"';
            $url = '#';
        } else {
            if ($urlTmpl) {
            	$url = str_replace('%%', $i, $urlTmpl);
            } else {
                $url = url($var.'='.($i));
                if ($i == $from || $i === 1) {
                	$url = preg_replace('~(&|\?)?'.$var.'=\d+~i', '', $url);
                }
            }
        }


        $text = $i + 1 - $from;
        if ($tag == 'span') {
            $pageLinks .= '<li'.$st.'><span class="link" data-href="'.$url.'">'.($i+1-$from).'</span></li> ';
        } else {
            $pageLinks .= '<li'.$st.'><a href="'.$url.'">'.($i+1-$from).'</a></li> ';
        }

        $j ++;
    }
    if ($pageCount > $floatLimit * 2) {
        $pageLinks .= '<li><a href="'.url($var.'='.($pageCount)).'">...</a></li> ';
    }
    $pageLinks .= '</ul>';
    return $pageLinks;
}

function url($qs)
{
    parse_str($qs, $inputParams);

    $url = $_SERVER['REQUEST_URI'];
    $urlArray = parse_url($url);
    $currentParams = array();
    if ($urlArray['query']) {
    	parse_str($urlArray['query'], $currentParams);
    }

    $data = array(
        'query' => $qs
    );

    $url = $urlArray['path'];

    foreach ($inputParams as $k => $v) {
        if ($v == '') {
        	unset($currentParams[$k]);
        } else {
            $currentParams[$k] = $v;
        }
    }
    if (count($currentParams)) {
    	$newQuery = http_build_query($currentParams);
        $url .= '?'.$newQuery;
    }

    return $url;
}

function printTable($data, $opts=[])
{
    $rows = '';

    $rows .= '<tr>';
    $headers = array_keys($data[0]);
    foreach ($headers as $key => $row) {
        if ($opts) {
        	$row = preg_replace('~(?<=.)_(?=.)~', '<br />', $row);
        }
       	$rows .= '<th>'.$row.'</th>';
    }
    $rows .= '</tr>';

    foreach ($data as $fieldInfo) {
    	$rows .= '<tr>';
        foreach ($fieldInfo as $k => $v) {
            $rows .= '<td>'.$v.'</td>';
        }
        $rows .= '</tr>';
    }
    echo '<table class="table table-pg">'.$rows.'</table>';
}

function formatSize($bytes) {
    if ($bytes < pow(1024, 1)) {
        return "$bytes b";
    } elseif ($bytes < pow(1024, 2)) {
        return round($bytes / pow(1024, 1), 2).' Kb';
    } elseif ($bytes < pow(1024, 3)) {
        return round($bytes / pow(1024, 2), 2).' Mb';
    } elseif ($bytes < pow(1024, 4)) {
        return round($bytes / pow(1024, 3), 2).' Gb';
    }
}

function redirect($s)
{
    echo '<script type="text/javascript">location="'.$s.'"</script>';
}

function query($sql)
{
    global $conn;
    $result = pg_query($conn, $sql);
    if (!$result) {
        echo 'Ошибка запроса "'.$sql.'"';
        exit;
    }
    return $result;
}

function getData($sql)
{
    $result = query($sql);
    $data = array();
    while ($row = pg_fetch_assoc($result)) {
        $data []= $row;
    }
    return $data;
}

function getCountAll($table, $where)
{
    $data = getVal('SELECT COUNT(*) FROM '.$table.$where);
    return $data;
}

function getFields($table, $onlyNames=true)
{
    $data = getData('select * from INFORMATION_SCHEMA.COLUMNS where table_name = \''.$table.'\'');
    if ($onlyNames) {
        $a = array();
        foreach ($data as $k => $v) {
        	$a []= $v['column_name'];
        }
        return $a;
    }
    return $data;
}

function getFieldsFull($table)
{
    return getFields($table, false);
}

function getOne($sql)
{
    $data = getData($sql);
    if ($data[0]) {
        return $data[0];
    }
    return array();
}

function getVal($sql)
{
    $data = getOne($sql);
    return array_shift($data);
}

function listDatabases()
{
    $data = getData('SELECT * FROM pg_database WHERE datistemplate = false');
    return $data;
}

function listTables($onlyNames=true)
{
    $data = getData('SELECT * FROM information_schema.tables where table_schema=\'public\' ORDER BY table_name;');
    if ($onlyNames) {
    	$a = array();
        foreach ($data as $k => $v) {
        	$a []= $v['table_name'];
        }
        return $a;
    }
    return $data;
}

function listTablesFull()
{
    $tables = listTables();
    $tables = getData('SELECT * FROM pg_class WHERE relname IN (\''.implode('\', \'', $tables).'\') ORDER BY relname');
    return $tables;
}


function primaryKey($table)
{
    $keys = getData('SELECT
    i.relname AS indexname,
    pg_get_indexdef(i.oid) AS indexdef
    FROM pg_index x
    INNER JOIN pg_class i ON i.oid = x.indexrelid
    WHERE x.indrelid = \''.$table.'\'::regclass::oid AND i.relkind = \'i\'::"char"
    AND x.indisprimary');

    preg_match('~\("([^"]+)"\)~i', $keys[0]['indexdef'], $a);

    if (!$a[1]) {
    	$fields = getFields($table);
        if (in_array('id', $fields)) {
            return 'id';
        }
    }

    return $a[1];
}


?>