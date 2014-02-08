<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_role(ROLE_SYSTEMUSER);

require_once('totp.php');

$id = (int) $_REQUEST['id'];

$account = accountname($id);
$sure = user_is_sure();
if ($sure === NULL)
{
  $section='webmailtotp_overview';
  title("Zwei-Faktor-Anmeldung am Webmailer");
  are_you_sure("id={$id}", "Möchten Sie die Zwei-Faktor-Anmeldung für das Postfach »{$account}« wirklich entfernen?");
}
elseif ($sure === true)
{
  delete_totp($id);
  if (! $debugmode)
    header("Location: overview");
}
elseif ($sure === false)
{
  if (! $debugmode)
    header("Location: overview");
}


