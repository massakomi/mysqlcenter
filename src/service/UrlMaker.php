<?php

declare(strict_types=1);

namespace service;

/**
 *
 */
class UrlMaker
{
    /**
     * Основная функция для создания URL
     *
     * Произвольное количество параметров, которые воспринимаются как param, value строки запроса
     *   UrlMaker::make('part', 100, 'mode', 'delete');
     * При этом можно не указывать таблицу, базу данных или страницу - они будут браться автоматически
     *
     * Активно используется в PageLayout
     *
     * @return string
     */
    public static function make(): string
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
        $db = $msc->db;
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
     * Функция редактирует URL добавляя/заменяя значение переменной name на value
     * @param string $url
     * @param string $name
     * @param string $value
     * @return string
     */
    public static function edit(string $url, string $name, string $value) :string
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
            return str_replace("&", "&amp;", $url);
        }
    }
}
