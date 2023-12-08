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

foreach ($_POST as $key => $value) {
    if (strpos($key, "option-") === 0) {
        $id = substr($key, 7);
        $type = 'virtual';
        if ($value == 'manual') {
            $type = 'auto';
        } elseif ($value == 'off') {
            $type = 'none';
        } elseif ($value == 'nomail') {
            $type = 'nomail';
        }
        DEBUG('MAILCONFIG change request for id #' . $id . ' to ' . $value);
        change_domain($id, $type);
    }
    if (strpos($key, "dkim-") === 0) {
        $id = substr($key, 5);
        $type = 'none';
        if ($value == 'dkim') {
            $type = 'dkim';
        } elseif ($value == 'dmarc') {
            $type = 'dmarc';
        }
        DEBUG('DKIM change request for id #' . $id . ' to ' . $value);
        change_domain_dkim($id, $type);
    }
}

if (!$debugmode) {
    header('Location: domains');
    die();
}
