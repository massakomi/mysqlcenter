<div id="root"></div>

<script type="text/babel">

  function TableHead(props) {
    return (
      <table cellPadding="4" id="tableFormEdit">
        <thead>
        <tr>
          <th>Поле</th>
          <th>Тип</th>
          <th>Длина/значения</th>
          <th>Ноль</th>
          <th>По умолчанию</th>
          <th><span title="Автоинкремент">Au</span></th>
          <th><span title="Primary key">PR</span></th>
          <th><span title="Unique">UN</span></th>
          <th><span title="Index">IND</span></th>
          <th>-</th>
          <th><span title="Fulltext">FU</span></th>
          <th>Атрибуты</th>
          <th>После...</th>
        </tr></thead>
        <tbody>
        {props.children}
        </tbody>
      </table>
    );
  }

  class MSC_DrawFields extends React.Component {


    constructor(props) {
      super(props);
      this.state = {value: 0}
    }

    aiClick(k) {
      document.getElementById(`default${k}`).value = ''
    }

    clearKeys(k) {
      get('key1'+k).checked = false;
      get('key2'+k).checked = false;
      get('key3'+k).checked = false;
      return false;
    }

    render() {

      console.log(this.props)

      let POST = this.props.post || {};

      let array = this.props.array;

      // получение массива из post
      if (array.length === 0) {
        alert("TODO")
        Object.keys(POST.name).forEach(function(key) {
          //let name = POST.name[key]
        })
/*
        $array = array();
        foreach ($_POST['name'] as $key => $name) {
            if (empty($name)) {
                continue;
            }
            $object = new A;
            $object->Field   = $name;
            $object->Type    = strtolower($_POST['ftype'][$key]);
            if ($_POST['length'][$key] > 0) {
                $object->Type .= '('. $_POST['length'][$key].')';
            }
            if ($_POST['attr'][$key] == 'UNSIGNED ZEROFILL') {
                $object->Type .= ' UNSIGNED ZEROFILL';
            } else if ($_POST['attr'][$key] == 'UNSIGNED') {
                $object->Type .= ' UNSIGNED';
            }
            $object->Null    = isset($_POST['isNull'][$key]) ? 'YES' : '';
            if (isset($_POST['uni'][$key])) {
                $object->Key = 'UNI';
            }
            if (isset($_POST['mul'][$key])) {
                $object->Key = 'MUL';
            }
            $object->Key = $_POST['primaryKey'] == $name ? 'PRI' : '';
            $object->Default = $_POST['default'][$key];
            $object->Extra = '';
            if (isset($_POST['auto'][$key])) {
                $object->Extra   = 'AUTO_INCREMENT';
            }
            $array []= $object;
        }
* */
      }

      let keys = this.props.keys;

      // получение массива "предыдущих полей" полей
      let fields = ['FIRST']
      let previousFields = {}
      let prev = '';
      this.props.fields.forEach(function(field) {
        previousFields[field] = prev
        prev = field
        fields.push(field)
      })

      // создание селектора типов данных
      let columnTypes = [
        'VARCHAR', 'TINYINT', 'TEXT', 'DATE',
        'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT',
        'FLOAT', 'DOUBLE', 'DECIMAL',
        'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR',
        'CHAR', 'TINYBLOB', 'TINYTEXT', 'BLOB', 'MEDIUMBLOB', 'MEDIUMTEXT', 'LONGBLOB', 'LONGTEXT',
        'ENUM', 'SET', 'BOOLEAN', 'SERIAL'
      ];

      const trs = array.map((v, k) => {

        let NAME='', TYPE='', LENGTH='', DEFAULT='', ISNULL='', AUT='', extra='';
        let isUnsignedZero=false, isUnsigned=false;
        let PRI='', UNI='', MUL='', uniName='', mulName='';

        if (typeof v == 'object') {
          let a = v.Type.match(/\((.*)\)/)
          if (a) {
            LENGTH = a[1]
          }
          isUnsignedZero = v.Type.match(/unsigned zerofill/i) !== null;
          isUnsigned = v.Type.match(/unsigned/i) !== null;
          NAME = v.Field;
          TYPE = v.Type.replace(/\((.*)\).*/i, '').toUpperCase();
          DEFAULT = v.Default;
          ISNULL = v.Null === true || v.Null === "YES";
          AUT = v.Extra !== ""
          extra = (
            <React.Fragment>
              <select name="after[]" defaultValue={previousFields[v.Field]}>
                {fields.map((v) =>
                  <option key={v}>{v}</option>
                )}
              </select>
              <input type="hidden" name="afterold[]" defaultValue={previousFields[v.Field] || 'FIRST'} />
            </React.Fragment>
          )
          console.log(NAME)
          if (keys[NAME]) {
            Object.keys(keys[NAME]).forEach(function(keyName) {
              let key = keys[NAME][keyName];
              PRI = key === "PRI" || POST.primaryKey === NAME
              if (key === "UNI" || POST.uni && POST.uni[NAME]) {
                UNI = true
                uniName = keyName
              }
              if (key === "MUL" || POST.mul && POST.mul[NAME]) {
                MUL = true
                mulName = keyName
              }
            });
          }

        } else if (POST.afterOption) {
          let checked = ''
          if (POST.afterOption === 'end') {
            checked = fields[fields.length - 1]
          } else if (POST.afterOption === 'field') {
            checked = POST.afterField
          }
          extra = (
            <React.Fragment>
              <select name={`after[${k}]`} defaultValue={checked}>
                {fields.map((v) =>
                  <option key={v}>{v}</option>
                )}
              </select>
              <input type="hidden" name={`afterold[${k}]`} defaultValue={checked} />
            </React.Fragment>
          )
        }

        let j = NAME === '' ? k : NAME;

        let attr = isUnsigned ? 'UNSIGNED' : (isUnsignedZero ? 'UNSIGNED ZEROFILL' : '');

        return (
          <tr key={k} id={`tableFormEditTr${k}`}>
            <td>
              <input name="name[]" tabIndex="1" id={`name${k}`} type="text" defaultValue={NAME} size="15" />
              <input type="hidden" name="oldname[]" defaultValue={NAME} />
            </td>
            <td>
              <select name="ftype[]" tabIndex="2" id={`typeSelectorId${k}`} defaultValue={TYPE}>
                {columnTypes.map((v) =>
                  <option key={v}>{v}</option>
                )}
              </select>
            </td>
            <td><input name="length[]" tabIndex="3" id={`length${k}`} type="text" defaultValue={LENGTH} size="30" /></td>
            <td><input name={`isNull[${k}]`} tabIndex="4" id={`isNull${k}`} type="checkbox" value="1" defaultChecked={ISNULL} /></td>
            <td><input name="default[]" tabIndex="5" id={`default${k}`} type="text" size="10" defaultValue={DEFAULT} /></td>
            <td><input name={`auto[${k}]`} tabIndex="6" id={`auto${k}`} onClick={this.aiClick.bind(this, k)} type="checkbox" value="1" defaultChecked={AUT}  /></td>
            <td><input name="primaryKey" tabIndex="7" id={`key1${k}`} type="radio" defaultValue={j} defaultChecked={PRI} /></td>
            <td><input name={`uni[${j}]`} tabIndex="8" id={`key2${k}`} type="checkbox" defaultValue={uniName} defaultChecked={UNI} /></td>
            <td><input name={`mul[${j}]`} tabIndex="9" id={`key3${k}`} type="checkbox" defaultValue={mulName} defaultChecked={MUL}  /></td>
            <td><a href="#" onClick={this.clearKeys.bind(this, k)}>clear</a></td>
            <td><input name={`fulltext[${k}]`} tabIndex="11" id={`fulltext${k}`} type="checkbox" value="1" /></td>
            <td>
              <select name="attr[]" tabIndex="12" id={`attr${k}`} style={{width:'70px'}} defaultValue={attr}>
                <option value="">-</option>
                <option>UNSIGNED</option>
                <option>UNSIGNED ZEROFILL</option>
              </select>
            </td>
            <td>{extra}</td>
          </tr>
        )
      });

      return (
        <TableHead>
          {trs}
        </TableHead>
      );
    }
  }


  class App extends React.Component {

    constructor(props) {
      super(props);
      this.state = {value: 'wait'};
    }

    removeRow(tableId, param) {
      removeRow(tableId, param)
    }

    componentDidMount() {

      // В зависимости от выбранного типа поля можно что-то изменить в других полях строки
      jQuery('[name="ftype[]"]').change(function() {
        var curType = jQuery(this).val();
        console.log(curType)
        if (curType === 'SERIAL') {
          var autoinc = jQuery(this).closest('tr').find('input:eq(5)');
          autoinc.attr('checked', true);
          autoinc.attr('disabled', true);
          var nulled = jQuery(this).closest('tr').find('input:eq(3)');
          nulled.attr('disabled', true);
        }
        if (curType === 'ENUM' || curType === 'SET') {
          var value = jQuery(this).closest('tr').find('[name="length[]"]');
          value.val("'','',''");
        }
      });

      jQuery('[name="table_name"]').focus()
    }


    /**
     * Специальная функция для изменения параметров скопированного ряда. Сначала копируется ряд.
     * Далее меняются индексы у аттрибутов name, если требуется. Ид и прочие аттрибуты не трогаются пока.
     */
    addDataRow(id) {
      var newTR = addRow(id);
      var inputs = newTR.getElementsByTagName('INPUT')
      for (var i = 0; i < inputs.length; i++) {
        var res = /([a-z]+)\[(\d+)\]/i.exec(inputs[i].name)
        if (res != null) {
          var nextName = res[1] + '['+ (Number(res[2]) + 1) +']';
          inputs[i].name = nextName;
        }
      }
    }

    render() {

      return (
        <form method="post" action="" className="tableFormEdit" name="addForm">
          {this.props.showTableName &&
              <React.Fragment>
              <input tabIndex="1" type="text" name="table_name" size="40" defaultValue={this.props.tableName} /> имя таблицы <br />
              </React.Fragment>
            }
          {this.props.afterSql &&
            <input type="hidden" name="afterSql" value={this.props.afterSql} /> }
            <input type="hidden" name="action" value={this.props.action} />

            <img src={`${this.props.dirImage}nolines_plus.gif`} alt="" border="0" onClick={this.addDataRow.bind(this, 'tableFormEdit')} title="Добавить поле" style={{cursor: 'pointer'}} />
            <img src={`${this.props.dirImage}nolines_minus.gif`} alt="" border="0" onClick={this.removeRow.bind(this, 'tableFormEdit', 'end')}  title="Удалить поле" style={{cursor: 'pointer'}} /><br />

            <MSC_DrawFields {...this.props} />

            <input tabIndex="100" type="submit" value="Выполнить!" className="submit" />
        </form>
      );
    }
  }

  let options = <?=json_encode($pageProps)?>;

  ReactDOM.render(
    <App {...options} />,
    document.getElementById('root')
  );

</script>
