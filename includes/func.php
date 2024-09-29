<?php
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

/**
 * Создаёт определение поля из объекта или на основе параметров, со свойствами поля (field, type...)
 *
 * @package sql
 * @param mixed  Либо field-объект, либо тип поля (в случае указания параметров по отдельности)
 * @param string Значение Null field-объекта (YES|NO - строка, определяющая, является ли поле NULL)
 * @param string Значение по умолчанию
 * @param string Значение Extra field-объекта
 * @param string Длина поля, если необходимо
 * @return string Определение поля (field definition)
 */
function getFieldDefinition($type=null, $null=null, $default=null, $extra=null, $length=null) {
    //echo "<br />$type, $null, $default, $extra, $length";
    if (is_object($type)) {
        foreach ($type as $param => $value) {
            $param = strtolower($param);
            $$param = $value;
        }
        if (stristr($type, 'UNSIGNED')) {
            $extra .= ' UNSIGNED';
            $type   = str_ireplace('UNSIGNED', '', $type);
        }
        if (stristr($type, 'ZEROFILL')) {
            $extra .= ' ZEROFILL';
            $type = str_ireplace('ZEROFILL', '', $type);
        }
        if (preg_match('~\((.*)\)~U', $type, $length)) {
            $length = $length[1];
            $type = trim(str_replace('('.$length.')', '', $type));
        }
    }
    $type = strtoupper($type);
    // особый тип, без доп. параметров
    if ($type == 'SERIAL') {
        return 'SERIAL';
    }
    if ($type == 'VARCHAR') {
        if (!is_numeric($length) || $length > 255 || $length < 1) {
            $length = 255;
        }
        $type .= "($length)";
    } else if ($type == 'SET' || $type == 'ENUM') {
        if (empty($length)) {
            return false;
        }
        $type .= "($length)";
    } else if ($type == 'FLOAT' || $type == 'DOUBLE') {
        if (empty($length)) {
            return false;
        } else {
            $length = str_replace('.', ',', $length);
        }
        $type .= "($length)";
    } else if (is_numeric($length) && !stristr($type, 'text')) {
        $type .= "($length)";
    }
    $field_info  = $type;
    if (stristr($extra, 'UNSIGNED')) {
        // это алиас
        if ($type != 'BOOLEAN') {
            $field_info .= ' UNSIGNED';
        }
        $extra = str_replace('UNSIGNED', '', $extra); // UNSIGNED - после типа поля
    }
    if (stristr($extra, 'ZEROFILL')) {
        $field_info .= ' ZEROFILL';
        $extra = str_replace('ZEROFILL', '', $extra);
    }
    if ($null != 'YES') {
        $field_info .=  ' NOT NULL';
    }
    if (trim($extra) != null) {
        $field_info .= ' '.$extra;
    }
    if ($default != null) {
        if (is_numeric($default)) {
            $field_info .=  ' DEFAULT '.intval($default);
        } else {
            $field_info .=  ' DEFAULT "'.$default.'"';
        }
    }
    $field_info = str_ireplace('auto_increment', 'AUTO_INCREMENT', $field_info);
    //pre($field_info);
    return $field_info;
}

/**
 * Удаляет ключевое поле из таблицы, предварительно удаляя параметр auto_increment если есть
 *
 * @package sql
 * @param string  Имя таблицы
 * @return boolean Удачно или нет. Если PRIMARY KEY нет, возвращает пустую строку
 */
function dropPrimaryKey($tbl) {
    global $msc;
    $fields = getFields($tbl);
    foreach ($fields as $f) {
        if ($f->Key == 'PRI') {
            $definition = getFieldDefinition($f);
            $field      = $f->Field;
        }
    }
    if (isset($definition)) {
        if (stristr($definition, 'auto_increment')) {
            $definition = str_ireplace('auto_increment', '', $definition);
            $sql = 'ALTER TABLE `'.$tbl.'` CHANGE '.$field.' '.$field.' '.$definition;
            $msc->query($sql);
        }
        $sql = "ALTER TABLE `$tbl` DROP PRIMARY KEY";
        if ($msc->query($sql)) {
            return $msc->addMessage('Ключ удален', $sql, MS_MSG_SUCCESS);
        } else {
            return $msc->addMessage('Ошибка удаления ключа', $sql, MS_MSG_FAULT, mysqli_errorx());
        }
    }
    return '';
}


/**
 * Определение версии сервера в виде числа и строки
 *
 * @package sql
 * @return array Числовое и строковое значение версии
 */
function getServerVersion() {
    global $msc;
    $result = $msc->query('SELECT VERSION() AS version');
    if ($result !== false) {
        $row   = mysqli_fetch_array($result);
        $match = explode('.', $row[0]);
    }
    if (!isset($row)) {
        $vi = 32332;
        $vs = '3.23.32';
    } else{
        $vi = (int)sprintf('%d%02d%02d', $match[0], $match[1], intval($match[2]));
        $vs = $row[0];
    }
    return array($vi, $vs);
}

/**
 * Возвращает выборку в виде массива
 * Вопросы - надо ли опциональни при пусто типе возвращать result, и надо ли при неверном указании типа возвращать false
 * Вообще-то если тип всегда будет одинаков, то мы никогда не узнаем об ошибках.
 * Таким образом, функция всегда возвращает то, что требуется. Если есть ошибки в параметрах, возвращает false.
 * $array = select('SELECT Db, User FROM db');
 *
 * @package sql
 * @param string SQL-запрос
 * @param string Тип выборки каждого ряда: assoc|object|array|row. Если не указано, возвращается mysql result
 * @param string Из какого поля брать ключи результирующего массива. Если не указано / не найдено, то используются индексы
 * @param string Если надо вернуть простой одномерный массив значений, то здесь указывается, из какого поля брать значения.
 * @return mixed Если есть ошибки в параметрах, то false, иначе если $type='' - mysql result, иначе массив.
 */
function select($sql, $type='', $group='', $simple='') {
    global $msc;
    $types = array(
        'assoc','object','array','row'
    );
    if (empty($sql) || !in_array($type, $types)) {
        return false;
    }
    // в случае неудачи возвращает чистый bool(false), иначе resource(18) of type (mysql result)
    $result = $msc->query($sql);
    if ($type == '') {
        return $result;
    }
    if ($result === false) {
        //echo mysqli_errorx();
        return array(); // что возвращать?
    }
    $count = mysqli_num_rows($result);
    $array = array();
    $i = 0;
    while($row = call_user_func('mysqli_fetch_'.$type, $result)) {
        $key = $i;
        if ($group != '') {
            if (isset($row[$group])) {
                $key = $row[$group];
            } else {
                return false;
            }
        }
        if ($simple != '') {
            if (isset($row[$simple])) {
                $row = $row[$simple];
            } else {
                return false;
            }
        }
        $array [$key]= $row;
        $i ++;
    }
    return $array;
}

/**
 * Возвращает массив ключей таблицы в виде двумерного массива ([Поле][Имя ключа]
 *
 * @package sql
 * @param string  Имя таблицы
 * @return array
 */
function getTableKeys($table) {
    if (empty($table)) {
        return array();
    }
    global $msc;
    $keys = array();
    $res = $msc->query('SHOW KEYS FROM `'.$table.'`');
    if (!$res) {
        return array();
    }
    while ($row = mysqli_fetch_object($res)) {
         if ($row->Key_name == 'PRIMARY') {
            $keys [$row->Column_name][$row->Key_name]= 'PRI';
         } else {
            $keys [$row->Column_name][$row->Key_name]= $row->Non_unique == 0 ? 'UNI' : 'MUL';
         }
    }
    return $keys;
}


/**
 * Возвращает массив SQL объектов-полей таблицы $table
 *
 * @package sql
 * @param string таблица
 * @param boolean возвратить только массив имён полей
 * @return array Массив полей
 */
function getFields($table, $onlyNames=false) {
    if (empty($table)) {
        return array();
    }
    global $msc;
    $a = array();
    $table = str_replace('`', '``', $table );
    $result = $msc->query('SHOW FIELDS FROM `'.$table.'`');
    if (!$result) {
        return false;
    }
    while ($row = mysqli_fetch_object($result)) {
        if ($onlyNames) {
            $a []= $row->Field;
        } else {
            $a [$row->Field]= $row;
        }
    }
    return $a;
}

/**
 * Возвращает массив кодировок сервера.
 *
 * @package sql
 * @param boolean Возвратить полную инфорамцию в виде массива объектов, либо только массив кодировок
 * @return array
 */
function getCharsetArray($extended=false) {
    global $msc;
    $charsetList = array();
    $result = $msc->query('SHOW CHARACTER SET');
    while ($row = mysqli_fetch_array($result)) {
       $charsetList [$row['Charset']]= $extended ? $row : $row['Charset'];
    }
    ksort($charsetList);
    return $charsetList;
}

/**
 * Преобразует значение в sql-оптимальное значение для использования в запросе (edit,add). Значение либо
 * остаётся прежним (для чисел), либо становится NULL, либо закавычивается и экранируется
 *
 * @package sql
 * @param string Значение
 * @param string Тип поля
 * @param boolean Является ли значение NULL-пустым
 * @return string Результат
 */
function processValueType($value, $type, $isNull) {
    global $connection;
    if ($isNull) {
        return 'NULL';
    } else {
        if (preg_match('~^[a-z]+int~iU', trim($type)) && !empty($value) && is_numeric($value)) {
            return $value;
        } else {
            return '"' . mysqli_escape_stringx($value) . '"';
        }
    }
}

function mysqli_escape_stringx($value)
{
    global $connection;
    return mysqli_escape_string($connection, $value);
}


/**
 * Возвращает ключ $name массива $_GET
 *
 * @package url
 * @param string Ключ
 * @param string Значение по умолчанию, если ключ не будет найден
 * @return mixed Значение параметра
 */
function GET($name, $default=null) {
    if (isset($_GET[$name])) {
        return $_GET[$name];
    } else {
        return $default;
    }
}

/**
 * Возвращает ключ $name массива $_POST
 *
 * @package url
 * @param string Ключ
 * @param string Значение по умолчанию, если ключ не будет найден
 * @return mixed Значение параметра
 */
function POST($name, $default=null) {
    if (array_key_exists($name, $_POST)) {
        $res = $_POST[$name];
        if (is_numeric(ini_get('magic_quotes_gpc'))) {
            $res = stripslashesRecursive($res);
        }
        return $res;
    } else {
        return $default;
    }
}


/**
 * Перенаправление на $url либо с помощью header, либо скриптом
 *
 * @package url
 * @param string URL
 */
function redirect($url) {
    if (!headers_sent()) {
        header('Location: '.$url);
        exit;
    } else {
        echo '
        <script language="javascript">
        window.location = "'.$url.'"
        </script>';
    }
}


/**
 * Аналог stripslashes(), но применяемый также рекурсивно к массивам
 *
 * @package string
 * @param mixed  Массив либо строка
 * @return mixed Обработанный массив либо строка
 */
function stripslashesRecursive($array) {
    if (is_array($array)) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array[$k] = stripslashesRecursive($v);
            } else {
                $array[$k] = stripslashes($v);
            }
        }
    } else {
        $array = stripslashes($array);
    }
    return $array;
}

/**
 * Создание селектора <SELECT>...</SELECT> на основе массива $array, с атрибутами $attributes
 * значениями будут ключи массива, текстом - значения массива, $checked - ключ selected элемента
 *
 * @package html
 * @param array   Массив значений для селектора
 * @param string  Аттрибуты тега SELECT
 * @param mixed   Ключ или массив ключей в массиве, OPTION которых будет выбран selected
 * @param string  Строка пробелов - базовый отступ (для красоты кода)
 * @param boolean Надо ли устанавливать прописывать ключи в аттрибуте value="" тегов OPTION
 * @param string  Дополнительный код после первого тега <SELECT>, обычно это пустые OPTIONs
 * @return string HTML код селектора
 */
function plDrawSelector($array, $attributes, $checked=null, $basetab='', $keyValue=true, $extra=null) {
    $s = $basetab.'<select'.$attributes.'>'."\r\n".$extra;
    $wasSelected = false; // флаг, чтобы 1 селектед только
    foreach ($array as $k => $v) {
        $sel = null;
        if ($checked == $k && !$wasSelected) {
            $sel = ' selected="selected"';
            $wasSelected = true;
        }
        $val = '';
        if ($keyValue) {
            $val = ' value="'.$k.'"';
        }
        $s .= $basetab.'  <option'.$val.''.$sel.'>'.$v.'</option>'."\r\n";
    }
    return $s .= $basetab.'</select>'."\r\n";
}

/**
 * Селектор даты полный
 *
 * @package html
 * @param integer Время
 * @param string  Префикс к именам полей
 * @param string HTML код селектора
 */
function plDrawDateSelector($time=null, $prf=null) {
    if (!function_exists('plDrawSelector')) {
        return null;
    }
    $plDrawDateSelectorPad = create_function('&$v', '$v = str_pad($v, 2, "0", STR_PAD_LEFT);');
    $time = ($time == null ? time() : $time);
    // Год
    $aYear = range(1900, date('Y'));
    arsort($aYear);
    $sYear = plDrawSelector($aYear, ' name="'.$prf.'year"', array_search(date('Y', $time), $aYear), '', false);
    // Месяц
    $months[0] = array(
    'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль',
    'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');
    $months[1] = array(
    'января', 'февраля', 'марта', 'апреля', 'май', 'июня', 'июля',
    'августа', 'сентября', 'октября', 'ноября', 'декабря');
    $sMonth = plDrawSelector($months[1], ' name="'.$prf.'month"', date('m', $time)-1, '', true);
    // День
    $sDay = plDrawSelector(range(1,31), ' name="'.$prf.'day"', date('d', $time)-1, '', false);
    // Час
    $aHour = range(0,23);
    array_walk($aHour, $plDrawDateSelectorPad);
    $sHour = plDrawSelector($aHour, ' name="'.$prf.'hour"', date('H', $time)-1, '', false);
    // Минуты
    $aMinut = range(0,59);
    array_walk($aMinut, $plDrawDateSelectorPad);
    $sMinut = plDrawSelector($aMinut, ' name="'.$prf.'minut"', date('i', $time)-1, '', false);
    // Секунды
    $sSec = plDrawSelector($aMinut, ' name="'.$prf.'second"', date('s', $time)-1, '', false);
    return "$sDay . $sMonth . $sYear Время: $sHour : $sMinut : $sSec";
}



/**
 * Создаёт option теги для массива $array
 *
 * @package html
 * @param string Опция (opt=keys именами будут ключи массива opt=base значениями будут basename значений)
 * @param string выбранное значение
 * @return string HTML код селектора
 */
function draw_array_options($array, $opt = '', $selected_value = '') {
    $s = '';
    if (!is_array($array)) {
        return '';
    }
    foreach ($array as $k => $v) {
        $opt == 'keys' ? $value = "value='$k'" : $value = '';
        $opt == 'base' ? $v = basename($v) : true;
        if (!empty($selected_value) && $v == $selected_value) {
            $s .= "<option $value selected>$v</option>";
        } else {
            $s .= "<option $value>$v</option>";
        }
    }
    return $s;
}


/**
 * Получить максимальный допустимый размер аплоада файла
 *
 * @package file
 * @return integer Размер в байтах
 */
function getMaxUploadSize() {
    if (!$filesize = ini_get('upload_max_filesize')) {
        $filesize = "5M";
    }
    $max_upload_size = get_real_size($filesize);
    if ($postsize = ini_get('post_max_size')) {
        $postsize = get_real_size($postsize);
        if ($postsize < $max_upload_size) {
            $max_upload_size = $postsize;
        }
    }
    return $max_upload_size;
}


/**
 * Считывает (и распаковывает сжатый) файл в строку
 *
 * @package file
 * @param   string   Путь к файлу
 * @param   string   MIME тип файла, иначе определяется автоматически
 * @return  mixed    string контент файла либо boolean FALSE в случае ошибок
 */
function readZipFile($path, $mime = '') {
  if (!file_exists($path)) {
    return FALSE;
  }
  switch ($mime) {
    case '':
      $file = @fopen($path, 'rb');
      if (!$file) {
        return FALSE;
      }
      $test = fread($file, 3);
      fclose($file);
      if ($test[0] == chr(31) && $test[1] == chr(139)) return readZipFile($path, 'application/x-gzip');
      if ($test == 'BZh') return readZipFile($path, 'application/x-bzip');
      return readZipFile($path, 'text/plain');
    case 'zip':
/*
        include_once 'includes/pclzip.lib.php';


     $zip = new PclZip($path);

     if (($list = $zip->listContent()) == 0) {
        die("Error : ".$zip->errorInfo(true));
     }

     for ($i=0; $i<sizeof($list); $i++) {
        for(reset($list[$i]); $key = key($list[$i]); next($list[$i])) {
            echo "File $i / [$key] = ".$list[$i][$key]."<br>";
        }
        echo "<br>";
     }*/

    /*echo '<br />'.$path;
        $zip = zip_open($path);
 echo var_dump($zip);
        if ($zip) {

            while ($zip_entry = zip_read($zip)) {
                echo "Name:               " . zip_entry_name($zip_entry) . "\n";
                echo "Actual Filesize:    " . zip_entry_filesize($zip_entry) . "\n";
                echo "Compressed Size:    " . zip_entry_compressedsize($zip_entry) . "\n";
                echo "Compression Method: " . zip_entry_compressionmethod($zip_entry) . "\n";

                if (zip_entry_open($zip, $zip_entry, "r")) {
                    echo "File Contents:\n";
                    $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                    echo "$buf\n";

                    zip_entry_close($zip_entry);
                }
                echo "\n";

            }
var_dump($zip_entry);
            zip_close($zip);

        } else {

        }
*/


      break;
    case 'text/plain':
      $file = @fopen($path, 'rb');
      if (!$file) {
        return FALSE;
      }
      $content = fread($file, filesize($path));
      fclose($file);
      break;
    case 'application/x-gzip':
      if (function_exists('gzopen')) {
        $file = @gzopen($path, 'rb');
        if (!$file) {
          return FALSE;
        }
        $content = '';
        while (!gzeof($file)) {
          $content .= gzgetc($file);
        }
        gzclose($file);
      } else {
        return FALSE;
      }
       break;
    case 'application/x-bzip':
      if (@function_exists('bzdecompress')) {
        $file = @fopen($path, 'rb');
        if (!$file) {
          return FALSE;
        }
        $content = fread($file, filesize($path));
        fclose($file);
        $content = bzdecompress($content);
      } else {
        return FALSE;
      }
       break;
    default:
       return FALSE;
  }
  return $content;
}

/**
 * Возвращает реальный размер в байтах строкового php ini  представления числа
 *
 * @package number
 * @param string   Строковое php ini представление
 * @return integer Размер файла в байтах
 */
function get_real_size($size=0) {
    if (!$size) {
        return 0;
    }
    $scan['MB'] = 1048576;
    $scan['Mb'] = 1048576;
    $scan['M']  = 1048576;
    $scan['m']  = 1048576;
    $scan['KB'] = 1024;
    $scan['Kb'] = 1024;
    $scan['K']  = 1024;
    $scan['k']  = 1024;
    foreach (array_keys($scan) as $key) {
        if ((strlen($size)>strlen($key))&&(substr($size, strlen($size) - strlen($key))==$key)) {
            $size = substr($size, 0, strlen($size) - strlen($key)) * $scan[$key];
            break;
        }
    }
    return $size;
}


/**
 * Преобразует размер в байтах в строковое смотрибельное представление в форме " .. Kb .. Mb"
 *
 * @package number
 * @param integer Размер файла в байтах
 * @return string Строковое представление
 */
function formatSize($bytes) {
    if ($bytes < pow(1024, 1)) {
        return "$bytes b";
    } else if ($bytes < pow(1024, 2)) {
        return round($bytes / pow(1024, 1), 2).' Kb';
    } else if ($bytes < pow(1024, 3)) {
        return round($bytes / pow(1024, 2), 2).' Mb';
    } else if ($bytes < pow(1024, 4)) {
        return round($bytes / pow(1024, 3), 2).' Gb';
    }
}


/**
 * Округляет число, выравнивая нули (модификация round()). Например, '2' => '2.0'
 *
 * @package number
 * @param float   Округляемое значение
 * @param integer Количество знаков после точки, которое надо дозаполнить нулями
 * @return string Строковое значение числа
 */
function MSC_roundZero(float $number, int $precision):int {
    $number = round($number, $precision);
    if (strchr($number,".")){
        $begin = strpos($number, ".");
        $int = substr($number, 0, $begin);
        $float = substr($number , $begin + 1);
        $number = "$int.$float" . str_repeat("0", $precision - strlen($float));
    } else {
        $number = $number . "." . str_repeat("0", $precision);
    }
    return $number;
}


/**
 * Распечатка объекта в таблицу
 *
 * @package debug
 * @param mixed   Либо ассоциативный массив, либо mysql result
 * @param boolean Распечатать ТОЛЬКО первый элемент! Причём сделает он это вертикально!
 * @param object  Передаваемый объект Table, можно заранее задать какие-то свои значения, стили
 * @param array   Массив HTML аттрибутов к ключам массива
 * @return string HTML код таблицы
 */
function MSC_printObjectTable($object, $first=false, $table=null, $attributes=array()) {
    if (!is_object($table)) {
        $table = new Table('contentTable');
        $table->setInterlace('', '#eeeeee');
    }
    $headers = array();
    $dataArray = array();
    // Преобразование входного объекта/массива
    if (!is_array($object)) {
        while ($o = mysqli_fetch_assoc($object)) {
            $dataArray []= $o;
        }
    } else {
        $dataArray = $object;
        unset($object);
    }
    // Если первый элемент массив, печатаем его
    if (isset($dataArray[0])) {
        foreach ($dataArray as $o) {
            if ($first) {
                foreach ($o as $k => $v) {
                    if (isset($attributes[$k])) {
                        $k = '<span'.$attributes[$k].'>'.$k.'</span>';
                    }
                    $table->makeRow($k, $v);
                }
                break;
            }
            $data = array();
            if (count($headers) == 0) {
                foreach ($o as $k => $v) {
                    $headers []= $k;
                    $data []= $v;
                }
                $table->makeRow($headers);
                $table->makeRow($data);
                continue;
            }
            foreach ($o as $k => $v) {
                $data []= $v;
            }
            $table->makeRow($data);
        }
    } else {
        $table->makeRow('Параметр', 'Значение');
        foreach ($dataArray as $k => $v) {
            $table->makeRow($k, $v);
        }
    }
    return $table->make();
}

/**
 * Распечатка запроса в таблицу. Нигде не используется, используется для отладки.
 *
 * @package debug
 * @param string SQL запрос
 * @param string HTML код таблицы
 */
function printSqlTable($sql) {
    global $msc;
    $table = new Table('sqlTable', 1, 1, 0);
    $result = $msc->query($sql);
    while ($row = mysqli_fetch_object($result)) {
        if ($table->tableCont == null) {
            $a = array();
            foreach ($row as $k => $v) {
                $a []= $k;
            }
            $table->makeRowHead($a);
        }
        $a = array();
        foreach ($row as $k => $v) {
            $a []= $v;
        }
        $table->makeRow($a);
    }
    $c = '<style>
    .sqlTable {font:12px Arial;}
    .sqlTable td {vertical-align:top; background-color:white}
    </style>';
    return $c.$table->make();
}

/**
 * Печатает массивы и объекты для отладки
 * Аналог print_r(), но печатает внутри [pre] уменьшенным шрифтом
 *
 * @package debug
 * @param array Массив
 * @param boolean Надо ли делать htmlspecialchars (если массив содержит html, которые не будет виден через браузер)
 */
function pre($arrray, $html=false) {
    $a = print_r($arrray, 1);
    if ($html) {
        $a = htmlspecialchars($a);
    }
    echo '<pre style="font-size:11px">' . $a . '</pre>';
}


/**
 * Пишет сообщение в лог, добавляя дату/время
 *
 * @package debug
 * @param string  Сообщение
 * @param string  SQL запрос (добавляется к сообщению)
 */
function msclog($message, $sql=null) {
    global $connection;
    $logFile = 'error.log';
    if (!file_exists($logFile)) {
        $file = @fopen($logFile, 'w+');
    } else {
        $file = @fopen($logFile, 'a+');
    }
    $message = str_replace("\n", ' ', $message);
    if ($sql) {
        $sql = str_replace("\n", ' ', $sql);
    }
    $time    = date('d.m.y H:i:s ');
    $string  = "\n".$time.$message;
    if ($sql != null) {
        $string  .= '('.$sql.' '.mysqli_error($connection).')';
    }
    @fwrite($file, $string);
    @fclose($file);
}

/**
 * В случае назначния set_error_handler, перехватывает сообщения об ошибках. При наличии msclog() делает лог в файл.
 * Параметры передаются автоматически обработчиком ошибок
 *
 * @package debug
 */
function mscErrorHandler($errno, $errstr, $errfile, $errline) {
    global $mscGlobalErrorsCash;
    $logstr = $errstr.'['.$errfile.':'.$errline.']';
    if (!isset($mscGlobalErrorsCash)) {
        $mscGlobalErrorsCash = array();
    }
    if (!in_array($logstr, $mscGlobalErrorsCash)) {
        $mscGlobalErrorsCash []= $logstr;
    } else {
        return;
    }
    if ($errno == 8) {
        return;
    }
    if (stristr($errstr, 'Unable to save result set')) {
        $logstr  .= '('.mysqli_errorx().')';
    }
    $errno = str_pad($errno, 4, ' ', STR_PAD_LEFT);
    if (function_exists('msclog')) {
        msclog($errno.' '.$logstr);
    }
}


if (!function_exists('str_ireplace')) {
    function str_ireplace($search, $replace, $subject){
        $token = chr(1);
        $haystack = strtolower($subject);
        $needle = strtolower($search);
        while (($pos = strpos($haystack,$needle)) !== FALSE){
            $subject = substr_replace($subject, $token, $pos, strlen($search));
            $haystack = substr_replace($haystack, $token, $pos, strlen($search));
        }
        $subject = str_replace($token, $replace, $subject);
        return $subject;
    }
}

/**
 * Время форматирует в русское "Вчера-сегодня-позавчера и последние дни недели"
 *
 * @package date
 * @param string  Формат date() для обычного форматирование
 * @param integer Timestamp дата
 * @return string Отформатированная дата
 */
function date2rusString($format, $ldate) {
    // дата сегодня 00:00
    $tmsTodayBegin = strtotime(date('m').'/'.date('d').'/'.date('y'));
    // дата заданного времени 00:00
    $tmsBegin = strtotime(date('m',$ldate).'/'.date('d',$ldate).'/'.date('y',$ldate));
    $params   = array('Сегодня', 'Вчера', 'Позавчера');
    $weekdays = array('Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота');
    for ($i = 0; $i <= 6; $i ++ ) {
        $tms = $tmsTodayBegin - 3600 * 24 * $i;
        if ($tmsBegin == $tms) {
            if (isset($params[$i])) {
                return $params[$i].', '.date('H-i', $ldate);
            } else {
                return $weekdays[date('w', $ldate)].', '.date('H-i', $ldate);
            }
        }
    }
    return date($format, $ldate);
}

/**
 * (для tbl_data и tbl_compare) Обрабатывает значения полей базы данных перед выводом их в виде таблицы.
 * Обработка заключается в: для текстовых - htmlspecialchars+обрезка, для даты - отображение в поле id=tblDataInfoId
 * для нулевых значений - значение возвращается оформленным курсивом.
 *
 * @package data view
 * @param string Значение
 * @param string Тип поля
 * @return string Обработанное значение
 */
function processRowValue($v, $type) {
    if ($v === NULL) {
        $v = MS_NULL_DESIGN;
    } else {
        // Тексты
        if (stristr($type, 'blob')) {
            $v = htmlspecialchars($v);;
        }
        if (stristr($type, 'text') || stristr($type, 'char')) {
            $v = htmlspecialchars($v);
        }
        if (strlen($v) > MS_TEXT_CUT && GET('fullText') == '') {
            $v = substr($v, 0, MS_TEXT_CUT) . ' ...';
            //$v = wordwrap($v, 20, "<br />\r\n");
        }
        // дата
        if (stristr($type, 'int') && strlen($v) == 10 && is_numeric($v)) {
            $e = ' onmouseover="get(\'tblDataInfoId\').innerHTML=\''.date(MS_DATE_FORMAT, $v).'\'" onmouseout="get(\'tblDataInfoId\').innerHTML=\'\'"';
            $v = '<span class="dateString"'.$e.'>'.$v.'</span>';
        }
    }
    return $v;
}


/**
 * (для tbl_data и tbl_compare) Получить массив заголовков для таблицы данных. Заголовки для таблиц данных
 * формируются особым образом, с переносом.
 *
 * @package data view
 * @param array   Массив SQL объектов-полей (SHOW FIELDS...)
 * @param boolean С возможностью сортировки или без
 * @return array  Массив заголовков
 */
function getTableHeaders($fields, $sorts=true) {
    global $umaker;
    $headers   = array();
    $pk = array();
    $fieldsCount = count($fields);
    $fieldsNames = array();
    // фиксим урл после перехода со страницы
    if (GET('s') != 'tbl_data') {
        $umaker->url = UrlMaker::edit($_SERVER['REQUEST_URI'], 's', 'tbl_data');
    }
    foreach ($fields as $k => $v) {
        $isWrapped = (
        (
            strchr($v->Type, 'int') ||
            strchr($v->Type, 'enum') ||
            strchr($v->Type, 'float')
        ) &&
        MS_HEAD_WRAP &&
        strlen($v->Field) > MS_HEAD_WRAP + 2 &&
        $fieldsCount > 10 &&
        GET('fullText') == ''
        );
        $u = $umaker->switcher('order', $v->Field.'-', $v->Field);
        // HTML обработка
        if ($isWrapped) {
            $v->Field = wordwrap($v->Field, MS_HEAD_WRAP, '<br />', true);
        }
        $link = !$sorts ? $v->Field : "<a href='$u' class='sort' title='Сортировать'>$v->Field</a>";
        $headers[]= $link;
    }
    return $headers;
}

function mysqli_errorx() {
    global $connection;
    return mysqli_error($connection);
}


/**
 * Загружает класс
 *
 * @package msc
 * @param string Базовое имя файла без расширения в папке includes (предположительно - имя класса)
 */
function classLoad($className) {
    if (!class_exists($className)) {
        require_once DIR_MYSQL . 'includes/' . $className.'.php';
    }
}


/**
 * Возвращает значение указанного параметра конфигурации
 *
 * @package msc
 * @param string  Параметр
 * @param string  Значение по умолчанию, если параметра нет
 * @return string Значение
 */
function conf($param, $default='') {
    global $mscConfigCash;
    if (!isset($mscConfigCash)) {
        $mscConfigCash = array();
        $data = file(MS_CONFIG_FILE);
        foreach ($data as $k => $line) {
            if (empty($line) || substr_count($line, '|') < 3) {
                continue;
            }
            list($name, $title, $value, $type) = explode('|', trim($line));
            $mscConfigCash [$name]= $value;
        }
    }
    return isset($mscConfigCash[$param]) ? $mscConfigCash[$param] : $default;
}

/**
 * Ускоренное выполнение большого кол-ва запросов с логом
 *
 * @package msc
 * @param string База данных
 * @param string SQL запрос (передаётся по ссылке, чтобы снизить расход памяти)
 */
function execSql($db, &$sql, $log=true) {
    global $msc, $connection;
    $mysqlGenerationTime0 = round(array_sum(explode(" ", microtime())), 10);
    if (!$msc->selectDb($db)) {
        return $msc->addMessage('Не смог выбрать базу данных', null, MS_MSG_FAULT);;
    }
    if ($log) {
        $msc->logInFile($sql);
    }
    $sql = str_replace("\r\n", "\n", $sql);
    $array = explode(";\n", $sql);
    $errors = array();
    $c = 0;
    $affected = 0;
    $count = count($array);
    for ($i = 0; $i < $count; $i ++) {
        $q = trim($array[$i]);
        if (empty($q) || (strpos($q, '--') === 0 && strpos($q, "\n") === false)) {
            continue;
        }
        $c ++;
        if (!$msc->query($q)) {
            $errors []= $msc->error.' ('.substr($q, 0, 100).')';
        } else {
            $affected += mysqli_affected_rows($connection);
        }
    }
    $fault = count($errors);
    $succ = $c - $fault;
    $info = " $succ запросов выполнено, $fault неудач. ";
    if (count($errors) == 0) {
        $msc->addMessage('Запрос выполнен без ошибок - ' . $info, null, MS_MSG_SUCCESS);
    } else {
        $msc->addMessage('Запросы выполнен с ошибками' . $info, null, MS_MSG_FAULT, implode('<br />', $errors));
    }
    $mysqlGenerationTime = round(round(array_sum(explode(" ", microtime())), 10) - $mysqlGenerationTime0, 5);
    $msc->addMessage("Выполнено за $mysqlGenerationTime с.");
    $msc->addMessage("Затронуто рядов: $affected");
}



/*
    assocTable

    echo printTable($table, [
        'htmlspecialchars' => 0,
        'class' => 'table table-condensed',
        'style' => 'width:auto; margin:0 auto',
        'headers' => 0,
        'callbackValue' => function($header, $value) {
            if ($header == 'login') {
                $value = '<a href="?page=logs&login='.$value.'">'.$value.'</a>';
            }
            return $value;
        }
    ]);
*/
function printTable($offersData, $opts=[])
{
    if (!$offersData) {
        echo '<p>Пустой массив</p>';
        return ;
    }
    $hsc = isset($opts['htmlspecialchars']) ? $opts['htmlspecialchars'] : true;
    $hdr = isset($opts['headers']) ? $opts['headers'] : true;
    $attrs = '';
    if ($opts['style']) {
        $attrs = ' style="'.$opts['style'].'"';
    }
    /*
        // Вариант шапки без бутстрапа
        echo '
        <style type="text/css">
        table.tt {empty-cells:show; border-collapse:collapse; margin:10px 0}
        table.tt td {border:1px solid #ccc; padding: 3px; vertical-align: top;}
        table.tt tr:nth-child(odd) {background-color:#eee; }
        </style>
        <table class="tt">';
    */
    $class = $opts['class'] ?: 'table table-bordered table-condensed table-sm table-hover';
    echo '
    <table class="'.$class.'" '.$attrs.'>';
    $headers = array();
    foreach ($offersData as $vals) {
        if (is_array($vals)) {
            foreach ($vals as $k => $v) {
                $headers [$k]= $k;
            }
        }
    }
    if ($hdr) {
        echo '<tr>';
        foreach ($headers as $k => $v) {
            echo '<th>'.($hsc ? htmlspecialchars($k) : $k).'</th>';
        }
    }
    echo '</tr>';
    foreach ($offersData as $vals) {
        echo '<tr>';
        if (is_array($vals)) {
            foreach ($headers as $header) {
                $v = $vals[$header];
                $v = $hsc ? htmlspecialchars($v) : $v;
                if ($opts['callbackValue']) {
                    $v = call_user_func($opts['callbackValue'], $header, $v);
                }
                echo '<td>'.$v.'</td>';
            }
        } else {
            echo '<td>'.$vals.'</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
}


function getData($sql) {
    global $msc;
    $data = [];
    $res = $msc->query($sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $data [] = $row;
    }
    return $data;
}

function getDataAssoc($sql, $key, $value) {
    global $msc;
    $data = [];
    $res = $msc->query($sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $data [$row[$key]] = $row[$value];
    }
    return $data;
}

/**
 * @return bool
 */
function isajax() {
    return $_GET['ajax'];
}

function ajaxResult($data) {
    header('Content-Type: application/json');
    exit(json_encode($data, JSON_INVALID_UTF8_IGNORE));
}

function ajaxError($message) {
    ajaxResult([
        'status' => false,
        'messages' => $message
    ]);
}

function ajaxSuccess($message) {
    ajaxResult([
        'status' => true,
        'messages' => $message
    ]);
}

function ajaxResultWithMessages() {
    global $msc;
    $data = $msc->getMessagesData();
    foreach ($data as $key => $item) {
        if ($item['type'] == MS_MSG_ERROR || $item['type'] == MS_MSG_FAULT) {
            ajaxError($data);
        }
    }
    ajaxSuccess($data);
}

function exitError($message) {
    if (isajax()) {
        global $msc;
        $msc->addMessage($message, null, MS_MSG_FAULT);
        ajaxResultWithMessages();
    } else {
        exit($message);
    }
}