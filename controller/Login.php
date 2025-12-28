<?php

namespace controller;

/**
 *
 */
class Login extends Base
{
    public function defaultAction(): array
    {
        global $msc;
        $msc->pageTitle = 'Login';
        return json_decode(file_get_contents(MS_CONNECT_CONFIG_FILE), true);
    }
}
