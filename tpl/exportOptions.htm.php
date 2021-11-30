
<?php
$a = 'type="checkbox" value="1" className="l2"';
$structChecked = !isset($structChecked) ? '  defaultChecked' : $structChecked;

$selectFieldForExport = '';
if ($_GET['table']) {
    $fields = getFields($_GET['table']);
    $options = '';
    $fieldsValues = [];
    foreach ($fields as $k => $v) {
        $options .= '<option>'.$v->Field.'</option>';
        $fieldsValues []= $v->Field;
    }
    $selectFieldForExport .= '<br /> Выбрать поля для экспорта:<br />
    <select name="fields[]" multiple="multiple" defaultValue={'.json_encode($fieldsValues).'}>'.$options.'</select>';
}
?>

<style>
    LABEL { display:block}
    LABEL .l2 { margin:0 0 0 24px}
    TABLE.tableExport .l2 {margin-right: 5px}
    [name="fields[]"] {height:120px; width:150px;}
</style>


<script type="text/babel">

  class ExportOptions extends React.Component {

    constructor(props) {
      super(props);
      this.state = {value: 'wait'};
    }

    render() {

      return (
          <div>
            <table border="0" cellSpacing="0" cellPadding="2">
              <tbody><tr>
                <td>
                  <label htmlFor="f1"><input type="checkbox" value="1" name="export_struct" id="f1"<?php echo $structChecked?> /> Структура</label>

                  <label htmlFor="f2" title="Укажите эту опцию, если вы хотите заменить таблицу (команда DROP TABLE)">
                    <input <?php echo $a?> name="addDrop" id="f2" />
                    Добавить удаление таблицы
                  </label>

                  <label htmlFor="f3" title="Будут преобразованы команды: CREATE TABLE IF NOT EXISTS... и при удалении таблиц DROP TABLE IF EXISTS ...">
                    <input <?php echo $a?> name="addIfNot" id="f3" />
                    Добавить IF NOT EXISTS
                  </label>

                  <label htmlFor="f4" title="К каждой таблице будет добавлено AUTO_INCREMENT=текущее значение">
                    <input <?php echo $a?> name="addAuto" id="f4" />
                    Добавить значение AUTO_INCREMENT
                  </label>

                  <label htmlFor="f5" title="Оставьте эту опцию, чтобы быть уверенным, что всё пройдет гладко">
                    <input <?php echo $a?> name="addKav" id="f5" defaultChecked />
                    Обратные `кавычки` в названиях таблиц и полей
                  </label>

                  <label htmlFor="f12" title="Шапка к дампу с информацией о версиях ПО, а также заголовки таблиц">
                    <input type="checkbox" value="1" name="addComment" id="f12" defaultChecked />
                    Добавлять комментарии
                  </label>

                </td>
                <td>
                  <label htmlFor="f6"><input type="checkbox" value="1" name="export_data" id="f6" defaultChecked />Данные</label>

                  <label htmlFor="f7">
                    <input <?php echo $a?> name="insFull" id="f7" defaultChecked />
                    Указать все поля <img src="<?php echo MS_DIR_IMG?>i-help2.gif" title="В запросе будут перечислены все поля INSERT INTO table (fields...) VALUES (...), иначе перечисление полей пропускается. Используейте эту опцию, если вы не уверены, что порядок полей сохранится." className="helpimg" alt="" />
                  </label>

                  <label htmlFor="f8">
                    <input <?php echo $a?> name="insExpand" id="f8" />
                    Одним запросом <img src="<?php echo MS_DIR_IMG?>i-help2.gif" title="Все вставки будут осуществлены одним запросом вида INSERT INTO table VALUES (set1..), (set2...), (set3...) etc" className="helpimg" alt="" />
                  </label>

                  <label htmlFor="f9" >
                    <input <?php echo $a?> name="insZapazd" id="f9" />
                    DELAYED <img src="<?php echo MS_DIR_IMG?>i-help2.gif" title="DELAYED. Сервер сначала отправит запрос в буфер и если таблица используется, то вставку рядов приостановится. Когда таблица освободится, сервер начнёт выполнять запрос и вставлять строки, периодически проверяя, появились ли новые запросы к таблице. Если да, то вставка рядов будет снова приостановлена до того момента, как таблица снова освободится.
--- Эта опция полезна, когда немедленное обновление таблицы не требуется (например, при логах), а также если осуществляется множество запросов на вставку. Это даёт существенный прирост производительности при вставках и не задерживает обычную выборку. В то же время такие запросы медленнее обычных и вы должны быть уверенными, что они вам нужны." className="helpimg" alt="" />
                  </label>

                  <label htmlFor="f10">
                    <input <?php echo $a?> name="insIgnor" id="f10" />
                    IGNORE <img src="<?php echo MS_DIR_IMG?>i-help2.gif" title="IGNORE. Ошибки, которые происходят при выполнении INSERT запроса игнорируются, то есть статус сообщения об ошибке меняется с ERROR на WARNING. С IGNORE, неправильные значения исправляются до ближайших валидных значений и вставляются, warning`и появляются, но выражение выполняется. Кроме этого в выражениях вида INSERT IGNORE INTO sdf (a,b) VALUES (6,6), (2,2), (7,7) будут вставлены все значения за исключением дублирующих. При отсутствии опции IGNORE будут вставлены все значения ДО дублирующих и выскочит ошибка." alt="" className="helpimg" />
                  </label>

                  Тип экспорта
                  <select name="export_option">
                    <option>INSERT</option>
                    <option>UPDATE</option>
                    <option title="REPLACE работает точно так же, как INSERT, за исключением тех случаев, когда старая строка в таблице имеет те же значения, что и новая строка для полей с индексами PRIMARY KEY или UNIQUE. В этом случае старый ряд будет удалён перед вставкой нового ряда. REPLACE это собственное расширение MySQL. Он либо вставляет, либо удаляет и вставляет. Заметьте, что если таблица не имеет ключей PRIMARY KEY либо UNIQUE, то использование REPLACE не даст ничего. В этом случае он становится аналогичным INSERT">REPLACE</option>
                  </select>

                    <?=$selectFieldForExport?>
                </td>
              </tr></tbody>
            </table>

            <label htmlFor="f13"><input name="export_to" id="f13" type="radio" value="1" /> в архив</label>
            <label htmlFor="f14"><input name="export_to" id="f14" type="radio" value="2" defaultChecked /> в текст</label>
          </div>
      );
    }
  }

</script>