<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting.
Please see https://source.schokokeks.org for the newest source files.

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('config.php');
require_once('inc/debug.php');
require_once("inc/base.php");
require_once("inc/theme.php");

set_exception_handler('handle_exception');

if (!isset($_GET['go']) || !is_string($_GET['go'])) {
    die("No command");
}
$go = $_GET['go'];

/*
 sanitize $go
*/

// filenames can end with .php
if (substr($go, strlen($go) - 4) == '.php') {
    $go = substr($go, 0, strlen($go) - 4);
}

DEBUG($go);

// Can throw invalid open_basedir warnings,
// see https://bugs.php.net/52065
if (strstr($go, "..") or strstr($go, "./") or strstr($go, ":") or (!file_exists("modules/$go.php")) or (!is_file("modules/$go.php"))) {
    die("illegal command");
}
[$module, $page] = explode('/', $go, 2);
$page = str_replace('/', '-', $page);
if (!in_array($module, config('modules'))) {
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
ini_set('include_path', ini_get('include_path').':./modules/'.$module.'/include:');

/* Look where we are (but let the module override) */
$section = str_replace("/", "_", $go);

/* Let the module work */
include("modules/".$go.".php");

if ($output) {
    if (!isset($title)) {
        $title = '';
    }
    show_page($module, $page);
}
