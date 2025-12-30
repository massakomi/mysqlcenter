<?php
declare(strict_types=1);

namespace controller;

/**
 *
 */
class Config extends Base
{
    public function defaultAction(): array
    {
        global $msc;
        $data = file(MS_CONFIG_FILE);
        $msc->pageTitle = 'Настройка MySQL Center';
        return compact('data');
    }
}
