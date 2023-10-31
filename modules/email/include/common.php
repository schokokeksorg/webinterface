<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

function encrypt_mail_password($newpass)
{
    DEBUG("unencrypted PW: »" . $newpass . "«");
    require_once('inc/base.php');
    $newpass = crypt($newpass, '$6$' . random_string(8) . '$');
    DEBUG("encrypted PW: " . $newpass);
    return chop($newpass);
}
