<?php

declare(strict_types=1);

namespace controller;

use dto\ConnectConfig;

class Login extends Base
{
    /**
     * @return array<string>
     */
    public function defaultAction(): array
    {
        global $msc;
        $msc->pageTitle = 'Login';

        $data = $msc->config->getAllConfig();
        if (count($data) === 0) {
            $msc->error('No connection configurations found in the config file: '.MS_CONNECT_CONFIG_FILE);
        }

        return $data;
    }

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

    public function saveAction(): void
    {
        global $msc;
        $res = $msc->config->saveConfig($_POST['config'], $_POST['current']);
        if ($res) {
            $msc->success('Конфиг сохранен');
        } else {
            $msc->error('Ошибка сохранения конфига');
        }
    }

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
