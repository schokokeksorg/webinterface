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
require_role(ROLE_SYSTEMUSER);

require_once('totp.php');

$id = (int) $_REQUEST['totp'];

$sure = user_is_sure();
if ($sure === null) {
    $section = 'loginsecurity_overview';
    title("Zwei-Faktor-Anmeldung");
    are_you_sure("totp={$id}", "Möchten Sie die Zwei-Faktor-Anmeldung wirklich entfernen?");
} elseif ($sure === true) {
    delete_systemuser_totp($id);
    if (!$debugmode) {
        header("Location: overview");
    }
} elseif ($sure === false) {
    if (!$debugmode) {
        header("Location: overview");
    }
}
