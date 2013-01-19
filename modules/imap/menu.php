<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('include/hasaccount.php');
require_once('include/hasdomain.php');

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER && (user_has_accounts() || ! user_has_vmail_domain() || user_has_dotcourier_domain() ) )
{
  $menu["imap_accounts"] = array("label" => "IMAP/POP3", "file" => "accounts", "weight" => 10);
}
elseif ($role & ROLE_MAILACCOUNT)
{
  $menu["imap_chpass"] = array("label" => "Passwort ändern", "file" => "chpass", "weight" => 10);
}


?>
