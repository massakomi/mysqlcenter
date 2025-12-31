<?php

declare(strict_types=1);

namespace controller;

/**
 *
 */
final class Users extends Base
{
    #[\Override]
    public function defaultAction(): array
    {
        global $msc;
        $msc->pageTitle = 'Пользователи';
        return [

        ];
    }
}
