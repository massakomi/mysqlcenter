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

        $sql = 'SHOW ALL';
        $data = $msc->getData($sql);

        echo '<pre>'; print_r($data); echo '</pre>'; exit;










        $data = file(MS_CONFIG_FILE);
        $msc->pageTitle = 'Настройка MySQL Center';
        return compact('data');
    }
}
