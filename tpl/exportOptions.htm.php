<?php 
$a = 'type="checkbox" value="1" class="l2"';
$structChecked = !isset($structChecked) ? '  checked' : $structChecked;
?>
<style>
LABEL { display:block}
LABEL .l2 { margin:0 0 0 24px}
</style>

<table border="0" cellspacing="0" cellpadding="2">
  <tr>
    <td valign="top">
    <label for="f1"><input type="checkbox" value="1" name="export_struct" id="f1"<?php echo $structChecked?> /> Структура</label>

    <label for="f2" title="Укажите эту опцию, если вы хотите заменить таблицу (команда DROP TABLE)">
        <input <?php echo $a?> name="addDrop" id="f2" />
        Добавить удаление таблицы
    </label>
    
    <label for="f3" title="Будут преобразованы команды: CREATE TABLE IF NOT EXISTS... и при удалении таблиц DROP TABLE IF EXISTS ...">
        <input <?php echo $a?> name="addIfNot" id="f3" />
        Добавить IF NOT EXISTS
    </label>

    <label for="f4" title="К каждой таблице будет добавлено AUTO_INCREMENT=текущее значение">
        <input <?php echo $a?> name="addAuto" id="f4" />
        Добавить значение AUTO_INCREMENT
    </label>

    <label for="f5" title="Оставьте эту опцию, чтобы быть уверенным, что всё пройдет гладко">
        <input <?php echo $a?> name="addKav" id="f5" checked />
        Обратные `кавычки` в названиях таблиц и полей
    </label>
    
    <label for="f12" title="Шапка к дампу с информацией о версиях ПО, а также заголовки таблиц">
        <input type="checkbox" value="1" name="addComment" id="f12" checked="checked" />
        Добавлять комментарии
    </label>
    
    </td>
    <td valign="top">
    <label for="f6"><input type="checkbox" value="1" name="export_data" id="f6" checked="checked" />Данные</label>

    <label for="f7">
        <input <?php echo $a?> name="insFull" id="f7" checked />
        Указать все поля <img src="<?php echo MS_DIR_IMG?>i-help2.gif" title="В запросе будут перечислены все поля INSERT INTO table (fields...) VALUES (...), иначе перечисление полей пропускается. Используейте эту опцию, если вы не уверены, что порядок полей сохранится." class="helpimg" />
    </label>
    
    <label for="f8">
        <input <?php echo $a?> name="insExpand" id="f8" />
        Одним запросом <img src="<?php echo MS_DIR_IMG?>i-help2.gif" title="Все вставки будут осуществлены одним запросом вида INSERT INTO table VALUES (set1..), (set2...), (set3...) etc" class="helpimg" />
    </label>
    
    <label for="f9" >
        <input <?php echo $a?> name="insZapazd" id="f9" />
        DELAYED <img src="<?php echo MS_DIR_IMG?>i-help2.gif" title="DELAYED. Сервер сначала отправит запрос в буфер и если таблица используется, то вставку рядов приостановится. Когда таблица освободится, сервер начнёт выполнять запрос и вставлять строки, периодически проверяя, появились ли новые запросы к таблице. Если да, то вставка рядов будет снова приостановлена до того момента, как таблица снова освободится.
--- Эта опция полезна, когда немедленное обновление таблицы не требуется (например, при логах), а также если осуществляется множество запросов на вставку. Это даёт существенный прирост производительности при вставках и не задерживает обычную выборку. В то же время такие запросы медленнее обычных и вы должны быть уверенными, что они вам нужны." class="helpimg" />
    </label>
   
    <label for="f10">
        <input <?php echo $a?> name="insIgnor" id="f10" />
        IGNORE <img src="<?php echo MS_DIR_IMG?>i-help2.gif" title="IGNORE. Ошибки, которые происходят при выполнении INSERT запроса игнорируются, то есть статус сообщения об ошибке меняется с ERROR на WARNING. С IGNORE, неправильные значения исправляются до ближайших валидных значений и вставляются, warning`и появляются, но выражение выполняется. Кроме этого в выражениях вида INSERT IGNORE INTO sdf (a,b) VALUES (6,6), (2,2), (7,7) будут вставлены все значения за исключением дублирующих. При отсутствии опции IGNORE будут вставлены все значения ДО дублирующих и выскочит ошибка." class="helpimg" />
    </label>

    Тип экспорта 
    <select name="export_option">
      <option>INSERT</option>
      <option>UPDATE</option>
      <option title="REPLACE работает точно так же, как INSERT, за исключением тех случаев, когда старая строка в таблице имеет те же значения, что и новая строка для полей с индексами PRIMARY KEY или UNIQUE. В этом случае старый ряд будет удалён перед вставкой нового ряда. REPLACE это собственное расширение MySQL. Он либо вставляет, либо удаляет и вставляет. Заметьте, что если таблица не имеет ключей PRIMARY KEY либо UNIQUE, то использование REPLACE не даст ничего. В этом случае он становится аналогичным INSERT">REPLACE</option>
    </select>  
<?php
if ($_GET['table']) {
    echo '<br />
    Выбрать поля для экспорта:<br />
    <select name="fields[]" multiple="multiple" style="height:120px; width:150px;">';
    $fields = getFields($_GET['table']);
    foreach ($fields as $k => $v) {
        echo '<option selected>'.$v->Field.'</option>';
    }
    echo '</select>';
}


?>
      
    </td>
  </tr>
</table>

<label for="f13"><input name="export_to" id="f13" type="radio" value="1" /> в архив</label> 
<label for="f14"><input name="export_to" id="f14" type="radio" value="2" checked="checked" /> в текст</label>