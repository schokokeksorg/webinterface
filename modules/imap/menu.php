<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('include/hasaccount.php');
require_once('include/hasdomain.php');

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER && (user_has_accounts() || !user_has_vmail_domain() || user_has_dotcourier_domain())) {
    $menu["imap_accounts"] = ["label" => "IMAP/POP3", "file" => "accounts", "weight" => 10];
} elseif ($role & ROLE_MAILACCOUNT) {
    $menu["imap_chpass"] = ["label" => "Passwort ändern", "file" => "chpass", "weight" => 10];
}
