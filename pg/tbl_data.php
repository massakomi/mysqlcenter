<?php

if ($_GET['action'] == 'deleteRow') {
	query('DELETE FROM '.$_GET['table'].' WHERE '.$_GET['where']);
}

function getWhere($table)
{
    $where = '';
    if ($_POST['search']) {
        $search = $_POST['search'];
        $numeric = is_numeric($search);

        $fields = getFieldsFull($table);
        $where = [];
        $skipped = [];
        foreach ($fields as $fieldInfo) {
            $field = $fieldInfo['column_name'];
            $type = $fieldInfo['data_type'];
            if ($type == 'timestamp') {
                $where []= ''.$field.'::text LIKE \'%'.$search.'%\''."\n";

            } elseif (strpos($type, 'int') !== false || strpos($type, 'double') !== false) {
                if (!$numeric) {
                    $skipped []= 'Поиск по полю "'.$field.'" пропущен';
                    continue;
                }
                $where []= '"'.$field.'"='.$search.''."\n";
            } elseif (strpos($type, 'text') !== false || strpos($type, 'char') !== false) {
                $where []= '"'.$field.'" LIKE \'%'.$search.'%\''."\n";
            } elseif ($type == 'USER-DEFINED' || $type == 'real' || $type == 'boolean' || strpos($type, 'timestamp') !== false) {
                $skipped []= 'Поиск по полю "'.$field.'" пропущен';
            } else {
                $where []= '"'.$field.'"=\''.$search.'\''."\n";
            }
        }
        $where = implode(' OR ', $where);
        if ($skipped) {
        	err(implode('<br />', $skipped));
        }
    }

    else if ($_POST['where']) {
    	$where = $_POST['where'];
        $fields = getFields($table);
        $where = preg_replace('~[\'"]('.implode('|', $fields).')[\'"]~i', '$1', $where);
        $where = str_replace('"', '\'', $where);
        $where = preg_replace('~('.implode('|', $fields).')~i', '"$1"', $where);
    }

    else if ($_POST['id']) {
    	$pk = primaryKey($table);
        $where = '"'.$pk.'"='.$_POST['id'].''."\n";
    }

    if ($where) {
    	$where = ' WHERE '.$where;
    }
    return $where;
}

function generateRows($table, $limit, $offset, $where, &$sql)
{


    $sql = 'SELECT * FROM '.$table. $where;

    if ($_GET['order']) {
        if (strpos($_GET['order'], '-') === 0) {
        	$sql .= ' ORDER BY "'.substr($_GET['order'], 1).'" DESC';
        } else {
            $sql .= ' ORDER BY "'.$_GET['order'].'"';
        }
    }

    $sql .= ' LIMIT '.$limit.' OFFSET '.$offset;

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
        if ($_GET['order'] == $row) {
        	$u = url('order=-'.$row);
        } else {
            $u = url('order='.$row);
        }
       	$rows .= '<th><a href="'.$u.'">'.$row.'</a></th>';
    }
    $rows .= '</tr>';

    $pk = primaryKey($table);

    foreach ($data as $key => $row) {


        if ($pk) {
            $where = '"'.$pk.'"=\''.$row[$pk].'\'';
            $rows .= '<tr>
                <td class="p"><input type="checkbox" name="" value=""></td>
                <td class="p"><a href="?table='.$table.'&s=tbl_edit&where='.urlencode($where).'"><i class="glyphicon glyphicon-edit"></i></a></td>
                <td class="p"><a href="?table='.$table.'&s=tbl_data&action=deleteRow&where='.urlencode($where).'" class="confirm"><i class="glyphicon glyphicon-remove"></i></a></td>';
        } else {
            $rows .= '<tr><td class="p"></td><td class="p"></td><td class="p"></td>';
        }

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

<style type="text/css">
.table-pg td {white-space:nowrap;}
</style>

<h3>Таблица: <?=$table?> (<?=$countAll?> строк)</h3>

<div class="alert alert-info alert-sql"><?=$sql?></div>


<form action="?table=<?=$_GET['table']?>&s=tbl_data" method="post" class="form-inline top">
    <input type="text" name="search" value="<?=htmlspecialchars($_POST['search'])?>" style="width:100px;" placeholder="Поиск" class="form-control input-sm auto" />
    <input type="text" name="where" value="<?=htmlspecialchars($_POST['where'])?>" style="width:100px;" placeholder="Where" class="form-control input-sm auto" />
    <input type="text" name="id" value="<?=$_POST['id']?>" placeholder="ID" style="width:100px;" class="form-control input-sm" />
    <input type="submit" value="Go" class="btn btn-info btn-sm" style="display:none;" >
    <?php
    if (count($_POST)) {
        echo '<a href="?table='.$_GET['table'].'&s=tbl_data"><i class="glyphicon glyphicon-remove"></i></a>';
    }
    ?>
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

<script type="text/javascript">
$('.auto').focus(function() {
    $(this).css('width', '200px')
})
$('.auto').each(function() {
    if ($(this).val() != '') {
        $(this).css('width', '200px')
    }
})
</script>
