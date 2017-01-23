
<h3>Редактирование строки</h3>

<?php

$table = $_GET['table'];

$fields = getFieldsFull($table);

// echo '<pre>'; print_r($fields); echo '</pre>';

$data = getOne('SELECT * FROM '.$table.' WHERE '.$_GET['where']);
//echo '<pre>'; print_r($data); echo '</pre>';
?>

<form class="form-horizontal" style="margin-top:20px;">
<?php
foreach ($fields as $fieldInfo) {
    $field = $fieldInfo['column_name'];
    $value = $data[$field];
    $type = $fieldInfo['data_type'];
?>
  <div class="form-group">
    <label class="col-sm-1 control-label"><?=$field?></label>
    <div class="col-sm-11">
    <?php
    if ($type == 'text') {
        ?>
        <textarea placeholder="<?=$field?>" name="<?=$field?>" class="form-control"><?=htmlspecialchars($value)?></textarea>
        <?php
    } else {
    ?>
      <input type="text" class="form-control" placeholder="<?=$field?>" name="<?=$field?>" value="<?=htmlspecialchars($value)?>">
    <?php
    }
    ?>
    
    </div>
  </div>
<?php
}
?>
  <div class="form-group">
    <div class="col-sm-offset-1 col-sm-11">
      <button type="submit" class="btn btn-success">Сохранить</button>
      <button type="submit" class="btn btn-default">Отмена</button>
    </div>
  </div>
</form