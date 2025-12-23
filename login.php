<?php

$msc->pageTitle = 'Login';

if ($_POST) {
    //echo '<pre>'; print_r($_POST); echo '</pre>'; exit;
}

$pageProps = [

];
if (isajax()) {
    return $pageProps;
}

$this->template($pageProps);
