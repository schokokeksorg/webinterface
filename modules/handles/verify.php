<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
https://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('verify.php');
require_once('inc/security.php');

title("E-Mail-Adresse bestätigen");
$section = 'handles_list';

if (isset($_REQUEST['token']))
{
  $token = $_REQUEST['token'];
  $daten = verify_mail_token($token);
  if ($daten == NULL) {
    system_failure('Die E-Mail-Adresse konnte nicht verifiziert werden. Vielleicht ist der Link bereits abgelaufen.');
  } else  {
    update_mailaddress($daten);
    success_msg('Die E-Mail-Adresse wurde erfolgreich geändert');
    header('Location: /');
  }
}


?>
