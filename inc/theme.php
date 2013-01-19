<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

function show_page($path = NULL) 
{
  global $go, $title, $headline, $output, $module, $page, $html_header;
  if ($path) {
  	$module = $path;
  }
  $theme = config('theme');
  if (! $theme)
    $theme = 'default';
  $theme_path = "themes/$theme/";
  $candidates = array();
  if ($page) {
    $candidates[] = "{$theme_path}page-$module-$page.tpl.php";
  }
  $candidates[] = "{$theme_path}page-$module.tpl.php";
  $candidates[] = "{$theme_path}page.tpl.php";
  if ($page) {
    $candidates[] = "modules/{$module}/theme/page-$page.tpl.php";
  }
  $candidates[] = "modules/{$module}/theme/page.tpl.php";
  if ($page) {
    $candidates[] = "themes/default/page-$module-$page.tpl.php";
  }
  $candidates[] = "themes/default/page-$module.tpl.php";
  $candidates[] = "themes/default/page.tpl.php";

  $theme_file = NULL;
  foreach ($candidates AS $c) {
  	if (file_exists($c)) {
		$theme_file = $c;
		break;
	}
  }
  if (! file_exists($theme_file))
    die("cannot get any theme file");

  include('inc/top.php');
  if (!isset($title))
    $title = '';
  if (!isset($headline))
    $headline = $title;
  $content = $output;

  include($theme_file);
}

