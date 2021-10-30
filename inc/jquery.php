<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');

if (! defined('__JQUERY_INCLUDED')) {
    define('__JQUERY_INCLUDED', '1');
    global $prefix;
    html_header('
<link rel="stylesheet" href="'.$prefix.'external/jquery/ui/jquery-ui.min.css" />
<script type="text/javascript" src="'.$prefix.'external/jquery/jquery.min.js" ></script>
<script type="text/javascript" src="'.$prefix.'external/jquery/ui/jquery-ui.min.js" ></script>
');
}

function javascript($file = null, $module = null)
{
    global $go, $prefix;
    [$mod, $page] = explode('/', $go, 2);
    if (! $file) {
        $file = $page.'.js';
    }
    if (! $module) {
        $module = $mod;
    }
    if (file_exists('modules/'.$module.'/'.$file)) {
        html_header('
<script type="text/javascript" src="'.$prefix.'modules/'.$module.'/'.$file.'"></script>
');
    } else {
        DEBUG('Missing JS file: '.'modules/'.$module.'/'.$file);
        warning('Interner Fehler: Dieses Modul wollte JavaScript laden, das hat aber nicht geklappt.');
    }
}
