<?php

require_once('inc/debug.php');
$go = $_GET['go'];

/*
 sanitize $go
*/

if (strstr($go, "..") or strstr($go, "./") or strstr($go, ":") or (! file_exists("modules/$go")))
{
  die("illegal command");
}


/*
 contruct prefix
*/

global $prefix;
$prefix = "../";
$count = 0;
str_replace("/", "x", $go, $count);

$prefix = $prefix.str_repeat("../", $count);


require_once('session/start.php');

$output = "";
require_once("inc/base.php");
/* setup module include path */
ini_set('include_path',ini_get('include_path').':./modules/'.dirname($go).'/include:');

/* Let the module work */
include("modules/".$go);

$section = str_replace("/", "_", str_replace(".php", "", $go));

include('inc/top.php');
print $output;
include('inc/bottom.php');


?>
