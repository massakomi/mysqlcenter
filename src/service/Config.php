<?php


declare(strict_types=1);

namespace service;

define('MS_ENCRYPTION_KEY', 'your-32-char-secret-key-here-1234567890'); // 32 chars for AES-256
define('MS_ENCRYPTION_IV', '1234567890123456'); // 16 chars for AES-256-CBC

use dto\ConnectConfig;

class Config
{
    private function encryptPassword(string $password): string
    {
        return base64_encode(openssl_encrypt($password, 'AES-256-CBC', MS_ENCRYPTION_KEY, 0, MS_ENCRYPTION_IV));
    }

    private function decryptPassword(string $encrypted): string
    {
        $decrypted = openssl_decrypt(base64_decode($encrypted), 'AES-256-CBC', MS_ENCRYPTION_KEY, 0, MS_ENCRYPTION_IV);

        return $decrypted === false ? '' : $decrypted;
    }

    public function connectConfigExists(): bool
    {
        return file_exists(MS_CONNECT_CONFIG_FILE);
    }

    public function getConfig(): ConnectConfig
    {
        $json = file_get_contents(MS_CONNECT_CONFIG_FILE) ?: '';
        $settings = json_decode($json, true);
        $current = $settings['current'];
        $config = $settings['config'][$current];
        if (!$config) {
            return new ConnectConfig();
        }

        if (!empty($config['password'])) {
            $config['password'] = $this->decryptPassword($config['password']);
        }

        return new ConnectConfig(
            $config['host'],
            $config['port'],
            $config['database'],
            $config['user'],
            $config['password'],
            $config['driver'],
            $config['binPath'],
        );
    }

    public function getAllConfig(): array
    {
        if ($this->connectConfigExists() === false) {
            return [];

        }
        $json = file_get_contents(MS_CONNECT_CONFIG_FILE) ?: '';
        $settings = json_decode($json, true);
        foreach ($settings['config'] as &$setting) {
            if (!empty($setting['password'])) {
                $setting['password'] = $this->decryptPassword($setting['password']);
            }
        }

        return $settings;
    }

    public function saveConfig(string $configJson, string $current): false|int
    {
        $config = json_decode($configJson, true);
        // Encrypt passwords in all configs
        foreach ($config as &$conf) {
            if (!empty($conf['password'])) {
                $conf['password'] = $this->encryptPassword($conf['password']);
            }
        }
        $config = [
            'current' => $current,
            'config' => $config,
        ];
        $backupFile = MS_DIR_UPLOAD.'/backup_'.date('YmdHis').'_'.basename(MS_CONNECT_CONFIG_FILE);
        if (file_exists(MS_CONNECT_CONFIG_FILE)) {
            copy(MS_CONNECT_CONFIG_FILE, $backupFile);
        }
        $content = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return file_put_contents(MS_CONNECT_CONFIG_FILE, $content);
    }
}
