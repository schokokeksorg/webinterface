<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting.
Please see http://source.schokokeks.org for the newest source files.

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('config.php');
require_once('inc/debug.php');
require_once('inc/db_connect.php');
require_once("inc/base.php");
require_once("inc/theme.php");


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
list($module, $page) = explode('/', $go, 2);
$page = str_replace('/', '-', $page);
if (! in_array($module, config('modules')))
{
  die("inactive module");
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
$html_header = "";
require_once("inc/base.php");
/* setup module include path */
ini_set('include_path',ini_get('include_path').':./modules/'.$module.'/include:');

/* Look where we are (but let the module override) */
$section = str_replace("/", "_", $go);

/* Let the module work */
include("modules/".$go.".php");

if ($output)
{
  if (!isset($title)) {
    $title = '';
  }
  show_page($module, $page);
}

?>
