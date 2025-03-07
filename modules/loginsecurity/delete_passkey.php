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

require_once('passkey.php');

$id = (int) $_REQUEST['id'];

$pk = null;
$passkeys = list_passkeys();
foreach ($passkeys as $item) {
    if ($item['id'] == $id) {
        $pk = $item;
    }
}

if (!$pk) {
    system_failure("Invalid passkey");
}


$sure = user_is_sure();
if ($sure === null) {
    $section = 'loginsecurity_overview';
    title("Passkey löschen");
    are_you_sure("id={$id}", "Möchten Sie den Passkey #{$pk['id']} ({$pk['handle']}) wirklich entfernen?");
} elseif ($sure === true) {
    delete_systemuser_passkey($id);
    if (!$debugmode) {
        header("Location: overview");
    }
} elseif ($sure === false) {
    if (!$debugmode) {
        header("Location: overview");
    }
}
