<?php

declare(strict_types=1);

namespace controller;

final class Users extends Base
{
    /**
     * @return array<string>
     */
    #[\Override]
    public function defaultAction(): array
    {
        global $msc;
        $msc->pageTitle = 'Пользователи';

        return [];
    }

    /**
     * @throws \Exception
     */
    public function userDeleteAction(): void
    {
        global $msc;
        $user = POST('user');
        $sql = 'DROP USER `'.$user.'`';
        $result = $msc->execPdo($sql);
        if ($result) {
            $msc->success('Пользователь "'.$user.'" удален', $sql);
        } else {
            $msc->error('Ошибка удаления "'.$user.'"', $sql);
        }
    }

    /**
     * @throws \Exception
     */
    public function userAddAction(): void
    {
        global $msc;

        $username  = $_POST['databaseuser'];
        $database  = $_POST['database'];
        $databaseCreate  = $_POST['databaseCreate'];
        $userpass  = $_POST['userpass'];

        // Проверяем, может уже есть такой пользователь
        $sql = 'SELECT * FROM mysql.user WHERE User="'.$username.'"';
        $result = $msc->getData($sql);
        if ($result) {
            $msc->notice('Пользователь с именем "'.$username.'" уже существует');
        } else {
            // Сначала добавляем пользователя
            $sql = 'CREATE USER `'.$username.'` IDENTIFIED BY "'.$userpass.'"';
            $result = $msc->execPdo($sql);
            if ($result) {
                $msc->success('Пользователь "'.$username.'" добавлен', $sql);
            } else {
                $msc->error('Ошибка добавления пользователя "'.$username.'"', $sql);

                return;
            }
        }

        // Теперь добавляем базу данных
        if ($databaseCreate) {
            $sql = 'CREATE DATABASE `'.$databaseCreate.'`';
            $result = $msc->execPdo($sql);
            if ($result) {
                $msc->success('База данных "'.$databaseCreate.'" создана', $sql);
            } else {
                $msc->error('Ошибка создания базы данных "'.$databaseCreate.'"', $sql);

                return;
            }
            $database = $databaseCreate;
        }

        // Теперь наделяем привилегиями пользователя на эту базу
        $sql = 'GRANT ALL ON `'.$database.'`.* TO `'.$username.'`';
        $result = $msc->execPdo($sql);
        if ($result) {
            $str = 'Все права на базу "'.$database.'" выданы пользователю "'.$username.'"';
            $msc->success($str, $sql);
        } else {
            $msc->error('Ошибка наделения прав на базу "'.$database.'"', $sql);
        }
    }
}
