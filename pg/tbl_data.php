<?php

function getWhere($table)
{
    $where = '';
    if ($_POST['search']) {
        $search = $_POST['search'];
        $numeric = is_numeric($search);
        $fields = getFieldsFull($table);
        $where = array();
        foreach ($fields as $fieldInfo) {
            $field = $fieldInfo['column_name'];
            $type = $fieldInfo['data_type'];
            if (strpos($type, 'timestamp') !== false) {
                $where []= ''.$field.'::text LIKE \'%'.$search.'%\''."\n";

            } elseif (strpos($type, 'int') !== false) {
                if (!$numeric) {
                    continue;
                }
                $where []= '"'.$field.'"='.intval($search).''."\n";
            } elseif (strpos($type, 'text') !== false || strpos($type, 'char') !== false) {
                $where []= '"'.$field.'" LIKE \'%'.$search.'%\''."\n";
            } elseif ($type == 'USER-DEFINED' || $type == 'boolean') {
                echo 'Поиск по полю "'.$field.'" пропущен';
            } else {
                $where []= '"'.$field.'"=\''.$search.'\''."\n";
            }
        }
        $where = implode(' OR ', $where);
    }

    if ($_POST['id']) {
    	$pk = primaryKey($table);
        $where = '"'.$pk.'"='.intval($_POST['id']).''."\n";
    }

    if ($where) {
    	$where = ' WHERE '.$where;
    }
    return $where;
}

function generateRows($table, $limit, $offset, $where, &$sql)
{


    $sql = 'SELECT * FROM '.$table. $where.' LIMIT '.$limit.' OFFSET '.$offset;

    $data = getData($sql);

    if (!$data) {
        return ;
    }

    $rows = '';
    $rows .= '<tr>
    <th></th>
    <th></th>
    <th></th>';
    $headers = array_keys($data[0]);
    foreach ($headers as $key => $row) {
       	$rows .= '<th>'.$row.'</th>';
    }
    $rows .= '</tr>';

    foreach ($data as $key => $row) {
        $rows .= '<tr>
            <td class="p"><input type="checkbox" name="" value=""></td>
            <td class="p"><i class="glyphicon glyphicon-edit"></i></td>
            <td class="p"><i class="glyphicon glyphicon-remove"></i></td>';
        foreach ($row as $k => $v) {
        	$rows .= '<td>'.$v.'</td>';
        }
        $rows .= '</tr>';
    }
    return $rows;
}


$table = $_GET['table'];
$where = getWhere($table);
$countAll = getCountAll($table, $where);
$limit = 100;
$nav = pagination($limit, $countAll, $offset);
$rows = generateRows($table, $limit, $offset, $where, $sql);
?>

<h3>Таблица: <?=$table?> (<?=$countAll?> строк)</h3>

<div class="alert alert-info alert-sql"><?=$sql?></div>


<form action="" method="post" class="form-inline top">
    <input type="text" name="search" value="<?=$_POST['search']?>" style="width:200px;" placeholder="Поиск" class="form-control input-sm" />
    <input type="text" name="id" value="<?=$_POST['id']?>" placeholder="ID" style="width:100px;" class="form-control input-sm" />
    <input type="submit" value="Go" class="btn btn-info btn-sm" style="display:none;" >
</form>

<?php
if ($rows) {
    echo $nav;
    echo '<table class="table table-pg">'.$rows.'</table>';
    echo $nav;
} else {
    err('Ничего не найдено');
}
?>
