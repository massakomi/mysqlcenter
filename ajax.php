<?php 
/**
 * MySQL Center Менеджер Базы данных MySQL (c) 2007-2024
 */

if (!defined('DIR_MYSQL')) {
    define('DIR_MYSQL', dirname(__FILE__).'/');
}
require_once DIR_MYSQL . 'config.php';

$actPro = new ActionProcessor(true);

?>

$("#msAjaxQueryDiv").show()
$("#msAjaxQueryDiv").prepend("<div><?=str_replace('"', '\'', $msc->getMessages())?></div>");

if (typeof(msAjaxQueryDivTm) != 'undefined') {
    clearTimeout(msAjaxQueryDivTm);
}
msAjaxQueryDivTm = setTimeout(function() {
    $("#msAjaxQueryDiv").fadeOut()
}, 2000);