<?php

namespace controller;

/**
 *
 */
class Config
{

    public function __construct()
    {
        global $msc, $pagel;
        $data = file(MS_CONFIG_FILE);
        if (isajax()) {
            return compact('data');
        }

        $msc->pageTitle = 'Настройка MySQL Center';

        $pagel->template([
            'data' => $data
        ]);
    }
}
