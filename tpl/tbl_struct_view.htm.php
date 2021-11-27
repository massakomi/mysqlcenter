<style>
    H4 {font-size:16px; margin:10px 0 10px; background-color:#eee; padding:10px; width:800px}
    SPAN.field {font-size:16px; padding:0 5px; border:1px solid #ccc; line-height:150%}
    TR.info TD {color:#aaa}
    TABLE.optionstable {empty-cells:show; border-collapse:collapse;}
    TABLE.optionstable TH {background-color: #eee}
    TABLE.optionstable TH, TABLE.optionstable TD {border:1px solid #ccc; padding: 2px 4px; vertical-align: top;}
</style>
<?php
$msc->pageTitle = 'Структура таблиц базы данных "'.$msc->db.'" ';

$fieldVariants = [];
foreach ($tables as $k => $v) {
    $fieldVariants [$v->Name]= $v->Name;
    $variant = preg_replace('~[s_]$~', '', $v->Name);
    $fieldVariants [$variant]= $v->Name;
    $fieldVariants ['id_'.$variant]= $v->Name;
    $fieldVariants [$variant.'_id']= $v->Name;
}

foreach ($tables as $table) {
    echo '<h4>'.$table->Name.' <span style="color:#aaa">'.$table->Rows.'</span></h4>';
    $fields = getFields($table->Name);


    echo '<table class="optionstable">';

    echo '<tr>';
    $comparedTables = array();
    foreach ($fields as $f) {
        if (array_key_exists($f->Field, $fieldVariants) && $fieldVariants[$f->Field] != $table->Name) {
            $comparedTables []= $fieldVariants[$f->Field];
        }
        $st = $tt = '';
        if ($f->Key == 'PRI') {
            if ($f->Extra != '') {
                $st .= 'background-color:#FF6600;';
                $tt .= 'priAI';
            } else {
                $st .= 'background-color:#FFCC00;';
                $tt .= 'pri';
            }
        }
        if ($f->Key == 'MUL') {
            $st .= 'background-color:#66CC99;';
            $tt .= 'index';
        }
        if ($f->Null != 'NO') {
            $st .= ';color:#aaa;';
            $tt .= ' null';
        } else {
            $tt .= ' not null';
        }
        echo '<th style="'.$st.'" title="'.$tt.'">'.$f->Field.'</th>';
    }
    echo '</tr>';

    if ($table->Rows > 0) {
        $result = $msc->query('SELECT * FROM '.$table->Name.' LIMIT 3');
        while ($row = mysqli_fetch_object($result)) {
            echo '<tr class="info">';
            foreach ($row as $v) {
                echo '<td>'.wordwrap( mb_substr(strip_tags($v), 0, 100), 50, '<br />', true).'</td>';
            }
            echo '</tr>';
        }
    }

    echo '</table>';

    if (count($comparedTables) > 0) {
        echo ' &nbsp; -> &nbsp;'.implode(' &nbsp; -> &nbsp;', $comparedTables);
    }
}
return;
