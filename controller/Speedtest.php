<?php

namespace controller;

/**
 *
 */

class Speedtest extends Base
{
    public function defaultAction(): array
    {
        global $msc;
        $msc->pageTitle = 'Тест скорости';

        //$start = round(round(array_sum(explode(" ", microtime())), 10) - $msc->timer, 5);

        $start = round(array_sum(explode(" ", microtime())), 10);

        function tquery($sql)
        {
            global $msc;
            $result = $msc->execPdo($sql);
            if (!$result) {
                $msc->error('Ошибка', $sql);
            }
        }

        //$msc->addMessage('Создаем базу test', '', MS_MSG_SIMPLE);
        $sql = 'CREATE DATABASE `test`';
        tquery($sql);

        $msc->selectDb('test') or die('oooops');

        $msc->allowRepeatMessages = true;

        for ($i = 0; $i < 5; $i++) {
            $tname = '`test' . $i . '`';
            //$msc->addMessage('Создаем таблицу '.$tname.'', '', MS_MSG_SIMPLE);
            $sql = '
                CREATE TABLE ' . $tname . ' (
                  `id` INT NOT NULL AUTO_INCREMENT,
                  `content` VARCHAR(255),
                  `title` VARCHAR(200) NOT NULL,
                  PRIMARY KEY (id)
                )';
            tquery($sql);
            //$msc->addMessage('Добавляем 10 рядов <span>', '', MS_MSG_SIMPLE);
            for ($j = 1; $j <= 10; $j++) {
                $sql = 'INSERT INTO ' . $tname . ' VALUES (' . $j . ', "content' . $j . '", "content' . $j . '")';
                tquery($sql);
            }
        }

        //$msc->addMessage('Удаляем базу test', '', MS_MSG_SIMPLE);
        $sql = 'DROP DATABASE `test`';
        $msc->execPdo($sql);

        echo '<h1>Тест скорости запросов</h1>';
        $point = round(array_sum(explode(" ", microtime())), 10);
        echo $test1 = round($point - $start, 2);

        echo '<h1>Тест выполнения php</h1>';
        $a = 1;
        for ($i = 0; $i < 1000000; $i++) {
            $a *= 2 + $a + rand(1, 2);
        }
        echo $test2 = round(round(array_sum(explode(" ", microtime())), 10) - $point, 2);


        $f = fopen('logs/speedtest.txt', 'a+');
        fwrite($f, "\n" . date('Y.m.d H:i:s') . " sql=$test1 php=$test2");
        fclose($f);

        $msc->selectDb($msc->db);
    }
}
