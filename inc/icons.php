<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');

function icon_warning($title = '')
{
    global $prefix;
    return "<img src=\"{$prefix}images/warning.png\" style=\"height: 16px; width: 16px;\" alt=\"{$title}\" title=\"{$title}\" />";
}

function icon_enabled_phpxx($title, $major, $minor)
{
    global $prefix;
    return "<img src=\"{$prefix}images/ok-php$major$minor.png\" style=\"height: 16px; width: 16px;\" alt=\"{$title}\" title=\"{$title}\" />";
}

function icon_enabled_warning($title = '')
{
    global $prefix;
    return "<img src=\"{$prefix}images/ok-warning.png\" style=\"height: 16px; width: 16px;\" alt=\"{$title}\" title=\"{$title}\" />";
}

function icon_enabled($title = '')
{
    global $prefix;
    return "<img src=\"{$prefix}images/ok.png\" style=\"height: 16px; width: 16px;\" alt=\"{$title}\" title=\"{$title}\" />";
}


function icon_disabled($title = '')
{
    global $prefix;
    //return "";
    return "<img src=\"{$prefix}images/disabled.png\" style=\"height: 16px; width: 16px;\" alt=\"{$title}\" title=\"{$title}\" />";
}


function icon_ok($title = '')
{
    global $prefix;
    return "<img src=\"{$prefix}images/ok.png\" style=\"height: 16px; width: 16px;\" alt=\"{$title}\" title=\"{$title}\" />";
}



function icon_error($title = '')
{
    global $prefix;
    return "<img src=\"{$prefix}images/error.png\" style=\"height: 16px; width: 16px;\" alt=\"{$title}\" title=\"{$title}\" />";
}

function icon_edit($title = '')
{
    global $prefix;
    return "<img src=\"{$prefix}images/edit.png\" style=\"height: 16px; width: 16px;\" alt=\"{$title}\" title=\"{$title}\" />";
}

function icon_pwchange($title = '')
{
    global $prefix;
    return "<img src=\"{$prefix}images/pwchange.png\" style=\"height: 16px; width: 16px;\" alt=\"{$title}\" title=\"{$title}\" />";
}


function icon_add($title = '')
{
    global $prefix;
    return "<img src=\"{$prefix}images/add.png\" style=\"height: 16px; width: 16px;\" alt=\"{$title}\" title=\"{$title}\" />";
}



function icon_delete($title = '')
{
    global $prefix;
    return "<img src=\"{$prefix}images/delete.png\" style=\"height: 16px; width: 16px;\" alt=\"{$title}\" title=\"{$title}\" />";
}



function other_icon($filename, $title = '')
{
    global $prefix;
    return "<img src=\"{$prefix}images/{$filename}\" style=\"height: 16px; width: 16px;\" alt=\"{$title}\" title=\"{$title}\" />";
}
