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

if (!defined('__JAVASCRIPT_INCLUDED')) {
    define('__JAVASCRIPT_INCLUDED', '1');
    global $prefix;
    html_header('
<script src="' . $prefix . 'js/common.js"></script>
');
}

function javascript($file = null, $module = null)
{
    global $go, $prefix;
    [$mod, $page] = explode('/', $go, 2);
    if (!$file) {
        $file = $page . '.js';
    }
    if (!$module) {
        $module = $mod;
    }
    if (file_exists('modules/' . $module . '/' . $file)) {
        html_header('
<script src="' . $prefix . 'modules/' . $module . '/' . $file . '"></script>
');
    } else {
        DEBUG('Missing JS file: ' . 'modules/' . $module . '/' . $file);
        warning('Interner Fehler: Dieses Modul wollte JavaScript laden, das hat aber nicht geklappt.');
    }
}
