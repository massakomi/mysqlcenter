<?php

$msc->pageTitle = 'Login';

$pageProps = json_decode(file_get_contents(MS_CONNECT_CONFIG_FILE), true);

if (isajax()) {
    return $pageProps;
}

$this->template($pageProps);
