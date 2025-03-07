<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
    $alert = null;
    include_once('modules/webapps/include/freewvs.php');

    $result = load_results();
    $found = 0;
    foreach ($result as $app) {
        if ($app['state'] == 'vulnerable') {
            $found++;
        }
    }
    if ($found > 0) {
        $shortcuts[] = [ 'section' => 'Webserver',
            'weight'  => 40,
            'file'    => 'freewvs',
            'icon'    => 'warning.png',
            'title'   => 'Web-Anwendungen',
            'alert'   => "{$found} unsicher", ];
    }
}
