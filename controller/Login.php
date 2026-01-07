<?php

declare(strict_types=1);

namespace controller;

use dto\ConnectConfig;

/**
 *
 */
class Login extends Base
{
    /**
     * @return array<string>
     */
    public function defaultAction(): array
    {
        global $msc;
        $msc->pageTitle = 'Login';
        return json_decode(file_get_contents(MS_CONNECT_CONFIG_FILE) ?: '', true);
    }

    /**
     * @return void
     */
    public function checkAction(): void
    {
        global $msc;
        $config = json_decode($_POST['config'], true);
        $config = $config[$_POST['current']];
        $config = new ConnectConfig(
            $config['host'],
            $config['port'],
            $config['database'],
            $config['user'],
            $config['password'],
            $config['driver'],
        );
        try {
            $msc->connectPdo($config);
            $msc->success('Connect success');
        } catch (\PDOException $e) {
            $msc->error($e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function saveAction(): void
    {
        global $msc;
        $res = $msc->saveConfig($_POST['config'], $_POST['current']);
        if ($res) {
            $msc->success('Конфиг сохранен');
        } else {
            $msc->error('Ошибка сохранения конфига');
        }
    }

    /**
     * @return void
     */
    public function openAction(): void
    {
        global $msc;
        $this->saveAction();
        $msc->connect();
        if ($msc->connected()) {
            $msc->success('Connect success!');
        }
    }
}
