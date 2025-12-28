<?php

/**
 *
 */
class UrlMaker
{
    /**
     * База данных в функции make() может автоматически браться либо из $_GET, либо из $msc
     * Эта переменная указывает на то, чтобы брать базу данных из $_GET
     */
    public bool $useGet = false;

    /**
     * Строка URL, которая используется как базовая (для switcher)
     */
    public string $url;

    /**
     * Конструктор. Определяет $this->url
     * @access private
     */
    public function __construct()
    {
        $this->url = $_SERVER['REQUEST_URI'];
    }


    /**
     * Основная функция для создания URL
     *
     * Произвольное количество параметров, которые воспринимаются как param, value строки запроса
     *   $umaker->make('part', 100, 'mode', 'delete');
     * При этом можно не указывать таблицу, базу данных или страницу - они будут браться автоматически
     *
     * Активно используется в PageLayout
     *
     * Если надо использовать БД из $_GET, применяйте код:
     *  $umaker->useGet = true;
     *  $url = $umaker->make('part', 100, 'mode', 'delete');
     *  $umaker->useGet = false;
     *
     * @return string
     */
    public function make(): string
    {
        global $msc;
        $values = func_get_args();
        $count = count($values);
        $array = [];
        $names = [];
        for ($i = 0; $i < $count; $i += 2) {
            if ($values[$i + 1] == '') {
                continue;
            }
            $array [] = $values[$i] . '=' . $values[$i + 1];
            $names [] = $values[$i];
        }
        $db = $this->useGet ? GET('db') : $msc->db;
        if ($db != '' && !in_array('db', $names)) {
            array_unshift($array, 'db=' . $db);
        }
        if ($msc->table != '' && !in_array('table', $names)) {
            array_unshift($array, 'table=' . $msc->table);
        }
        if ($msc->page != '' && !in_array('s', $names)) {
            array_unshift($array, 's=' . $msc->page);
        }
        return MS_URL . '?' . (count($array) > 0 ? implode('&', $array) : '');
    }

    /**
     * Переключает указанное значение $value1 на $value2 и обратно в УРЛ.
     * Если $value2 не указано, то перключает первое значение.
     *
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function switcher($name, $value1, $value2 = null): ?string
    {
        if (GET($name) == null) {
            return UrlMaker::edit($this->url, $name, $value1);
        } elseif ($value2 == null) {
            return UrlMaker::delete($this->url, $name);
        } else {
            return UrlMaker::edit($this->url, $name, GET($name) == $value1 ? $value2 : $value1);
        }
    }

    /**
     * Функция редактирует URL добавляя/заменяя значение переменной name на value
     */
    public static function edit($url, $name, $value)
    {
        $url = str_replace("&amp;", "&", $url);
        $first = strpos($url, ($name . "="));

        if (is_integer($first)) {
            $c = substr($url, $first - 1, 1);
            if (($c == "&") || ($c == "?")) {
                $result = substr($url, 0, $first);
                $p = strpos($url, "&", $first);
                if (is_integer($p)) {
                    $result .= substr($url, $p + 1);
                }
                return UrlMaker::edit($result, $name, $value);
            }
        } else {
            $p = strpos($url, "?");
            if (!is_integer($p)) {
                $url .= "?";
            } else {
                $c = substr($url, strlen($url) - 1, 1);
                if (($c != "&") && ($c != "?")) {
                    $url .= "&";
                }
            }
            $url .= $name . "=" . $value;
            $url = str_replace("&", "&amp;", $url);
            return $url;
        }
    }

    /**
     *
     */
    public function delete($url, $name)
    {
        $url = str_replace("&amp;", "&", $url);
        $first = strpos($url, ($name . "="));
        if (is_integer($first)) {
            $c = substr($url, $first - 1, 1);
            if (($c == "&") || ($c == "?")) {
                $result = substr($url, 0, $first);
                $p = strpos($url, "&", $first);
                if (is_integer($p)) {
                    $result .= substr($url, $p + 1);
                }
                $c = substr($result, strlen($result) - 1, 1);
                if ($c == "&") {
                    $result = substr($result, 0, strlen($result) - 1);
                }
                $result = str_replace("&", "&amp;", $result);
                return $result;
            }
        } else {
            return str_replace("&", "&amp;", $url);
        }
    }
}
