<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_role(ROLE_SYSTEMUSER);
require_once('inc/security.php');

include('subuser.php');
$section = 'subusers_subusers';

if (isset($_GET['subuser'])) {
    $sure = user_is_sure();
    if ($sure === null) {
        $subuser = load_subuser($_GET['subuser']);
        are_you_sure("subuser={$subuser['id']}", '
    <p>Soll der zusätzliche Admin-Zugang »' . $subuser['username'] . '« wirklich gelöscht werden?</p>');
    } elseif ($sure === true) {
        delete_subuser($_GET['subuser']);
        if (!$debugmode) {
            header('Location: subusers');
        }
        die();
    } elseif ($sure === false) {
        if (!$debugmode) {
            header("Location: subusers");
        }
        die();
    }
}
