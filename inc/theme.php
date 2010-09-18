<?php

function show_page($path = NULL) 
{
  global $go, $title, $headline, $output, $module, $page;
  if ($path) {
  	$module = $path;
  }
  $theme = config('theme');
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

