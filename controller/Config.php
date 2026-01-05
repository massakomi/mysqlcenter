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
}
