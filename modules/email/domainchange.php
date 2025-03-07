<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');
require_once('vmail.php');

require_once("inc/debug.php");
global $debugmode;

require_role(ROLE_SYSTEMUSER);

check_form_token('vmail_domainchange');

$newsetting = [];
$newdkimsetting = [];
foreach ($_POST as $key => $value) {
    if (strpos($key, "option-") === 0) {
        $id = substr($key, 7);
        $newsetting[$id] = 'virtual';
        if ($value == 'manual') {
            $newsetting[$id] = 'auto';
        } elseif ($value == 'off') {
            $newsetting[$id] = 'none';
        }
    }
    if (strpos($key, "nomail-") === 0) {
        $id = substr($key, 7);
        if ($value == 'nomail' && (!isset($newsetting[$id]) || $newsetting[$id] == 'none')) {
            $newsetting[$id] = 'nomail';
        }
    }
    if (strpos($key, "dkim-") === 0) {
        $id = substr($key, 5);
        $newdkimsetting[$id] = 'none';
        if ($value == 'dkim') {
            $newdkimsetting[$id] = 'dkim';
        } elseif ($value == 'dmarc') {
            $newdkimsetting[$id] = 'dmarc';
        }
    }
}
foreach ($newsetting as $id => $type) {
    $old = domainsettings($id);
    DEBUG('MAILCONFIG change request for id #' . $id . ' from ' . $old['type'] . ' to ' . $type);
    change_domain($id, $type);
    if (($old['type'] == 'none' || $old['type'] == 'nomail') && ($type == 'auto' || $type == 'virtual')) {
        // Default wenn man Mail-Verwendung einschaltet
        $newdkimsetting[$id] = 'dmarc';
    }
    if ($type == "nomail" || $type == "none") {
        // DKIM muss abgeschaltet sein, wenn das DKIM-UI nicht mehr angezeigt wird
        $newdkimsetting[$id] = 'none';
    }
}
foreach ($newdkimsetting as $id => $type) {
    DEBUG('DKIM change request for id #' . $id . ' to ' . $type);
    change_domain_dkim($id, $type);
}

if (!$debugmode) {
    header('Location: domains');
    die();
}
