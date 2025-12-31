<?php

declare(strict_types=1);

namespace controller;

/**
 *
 */
final class Config extends Base
{
    #[\Override]
    public function defaultAction(): array
    {
        global $msc;
        $data = file(MS_CONFIG_FILE);
        $msc->pageTitle = 'Настройка MySQL Center';
        return compact('data');
    }
}
