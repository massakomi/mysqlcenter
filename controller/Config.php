<?php

declare(strict_types=1);

namespace controller;

/**
 *
 */
final class Config extends Base
{
    /**
     * @return array<string>
     */
    #[\Override]
    public function defaultAction(): array
    {
        global $msc;
        $data = json_decode(file_get_contents(MS_CONFIG_FILE) ?: '');
        $msc->pageTitle = 'Настройка MySQL Center';
        return compact('data');
    }

    /**
     * @return void
     */
    public function updateAction(): void
    {
        global $msc;
        $json = json_decode(file_get_contents(MS_CONFIG_FILE) ?: '');
        $changed = false;
        foreach ($json as $item) {
            $newValue = POST($item->name);
            if ($item->type === 'integer' || $item->type === 'boolean') {
                if (intval($newValue) != intval($item->value)) {
                    $item->value = intval($newValue);
                    $changed = true;
                }
            } elseif ($newValue != $item->value) {
                $item->value = $newValue;
                $changed = true;
            }
        }
        if ($changed) {
            $content = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if (file_put_contents(MS_CONFIG_FILE, $content)) {
                $msc->success('Конфиг обновлён');
            } else {
                $msc->error('Не удалось записать конфиг в файл');
            }
        } else {
            $msc->error('Нечего обновлять');
        }
    }

    /**
     * @return void
     */
    public function restoreAction(): void
    {
        global $msc;
        if (copy(MS_CONFIG_DEFAULT_FILE, MS_CONFIG_FILE)) {
            $msc->success('Значения по умолчанию восстановлены');
        }
    }
}
