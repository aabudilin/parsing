<?php
$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/www';
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"]; 
define("NO_KEEP_STATISTIC", true); 
define("NOT_CHECK_PERMISSIONS", true); 
set_time_limit(0); 

require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_before.php");;

use Bitrix\Main\Loader;
Loader::includeModule("iblock");
Loader::includeModule("catalog");

require($_SERVER['DOCUMENT_ROOT'].'/php/import/homimi/WriteOffer.php');
require($_SERVER['DOCUMENT_ROOT'].'/php/import/homimi/MebelXmlParser.php');


$parser = new MebelXmlParser('https://homeme.ru/yml/', 15);
$parser->process();
$parser->viewLog();

?>