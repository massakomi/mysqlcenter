<?php

declare(strict_types=1);

namespace controller;

/**
 *
 */
final class Info extends Base
{
    #[\Override]
    public function defaultAction(): array
    {
        global $msc;
        $msc->pageTitle = 'Информация и конфигурация';
        return [
            'processes' => $msc->driver->getProcessList(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function pgLoadSettingsAction(): array
    {
        $content = file_get_contents(DIR_MYSQL . 'config/pg-runtime-config.json');
        return json_decode($content, true);
    }
}
