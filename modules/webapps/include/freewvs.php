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


function load_results()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT directory, docroot, first_seen, last_seen, first_warned, last_warned, appname, version, state, safeversion, vulninfo FROM qatools.detected_webapps WHERE uid=?", [$uid]);
    $ret = [];
    while ($line = $result->fetch()) {
        array_push($ret, $line);
    }
    return $ret;
}

function get_upgradeinstructions($appname)
{
    $result = db_query("SELECT url FROM qatools.freewvs_upgradeinstructions WHERE appname=?", [$appname]);
    if ($result->rowCount() > 0) {
        $tmp = $result->fetch();
        return $tmp[0];
    }
    return null;
}
