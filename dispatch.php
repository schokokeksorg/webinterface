<?php

require_once('config.php');
require_once('inc/debug.php');
$go = $_GET['go'];

/*
 sanitize $go
*/

// filenames can end with .php
if ( substr( $go, strlen( $go ) - 4 ) == '.php' ) {
  $go = substr( $go, 0, strlen($go) - 4);
}

DEBUG($go);

if (strstr($go, "..") or strstr($go, "./") or strstr($go, ":") or (! file_exists("modules/$go.php")) or (! is_file("modules/$go.php")))
{
  die("illegal command");
}
$tmp = explode('/', $go, 2);
$module = $tmp[0];
if (! in_array($module, $config['modules']))
{
  die("illegal command");
}


/*
 construct prefix
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

/* Look where we are (but let the module override) */
$section = str_replace("/", "_", str_replace(".php", "", $go));

/* Let the module work */
include("modules/".$go.".php");


include('inc/top.php');
print $output;
include('inc/bottom.php');


?>
