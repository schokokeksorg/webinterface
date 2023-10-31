<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('config.php');
require_once('inc/base.php');
$debugmode = (isset($_GET['debug']) && config('enable_debug'));

function DEBUG($str)
{
    global $debugmode;
    if ($debugmode) {
        echo "<pre>" . htmlspecialchars(print_r($str, true)) . "</pre>\n";
    }
}


DEBUG("GET: " . htmlentities(print_r($_GET, true)) . " / POST: " . htmlentities(print_r($_POST, true)));
