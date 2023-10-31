<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

function show_page($path = null)
{
    global $prefix, $go, $title, $headline, $output, $module, $page, $html_header, $footnotes;

    $styles = [];
    if (file_exists("modules/{$module}/style.css")) {
        $styles[] = "modules/{$module}/style.css";
    }
    foreach ($styles as $style) {
        html_header('<link rel="stylesheet" href="' . $prefix . $style . '" type="text/css" />' . "\n");
    }
    if ($path) {
        $module = $path;
    }
    $theme = config('theme');
    if (!$theme) {
        $theme = 'default';
    }
    $theme_path = "themes/$theme/";
    $candidates = [];
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

    $theme_file = null;
    foreach ($candidates as $c) {
        if (file_exists($c)) {
            $theme_file = $c;
            break;
        }
    }
    if (!file_exists($theme_file)) {
        die("cannot get any theme file");
    }

    include('inc/top.php');
    if (!isset($title)) {
        $title = '';
    }
    if (!isset($headline)) {
        $headline = $title;
    }
    $content = $output;

    include($theme_file);
}
