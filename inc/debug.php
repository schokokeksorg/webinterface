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

require_once('config.php');
require_once('inc/base.php');
$debugmode = (isset($_GET['debug']) && config('enable_debug'));

function DEBUG($str)
{
    global $debugmode;
    if ($debugmode) {
        if (is_string($str)) {
            echo htmlspecialchars($str) . "<br />\n";
        } else {
            echo "<pre>".htmlspecialchars(print_r($str, true))."</pre>\n";
        }
    }
}


DEBUG("GET: ".htmlentities(print_r($_GET, true))." / POST: ".htmlentities(print_r($_POST, true)));
