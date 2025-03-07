<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

$role = $_SESSION['role'];

require_once('include/hasdomain.php');
require_once('include/hasaccount.php');

if ($role & ROLE_SYSTEMUSER) {
    $menu["email_vmail"] = ["label" => "E-Mail", "file" => "vmail", "weight" => 3];
}
if ($role & ROLE_VMAIL_ACCOUNT) {
    $menu['email_edit'] = ["label" => "Einstellungen", "file" => "edit", "weight" => 10];
}
if ($role & (ROLE_VMAIL_ACCOUNT | ROLE_MAILACCOUNT)) {
    $menu['email_chpass'] = ["label" => "Passwort ändern", "file" => "chpass", "weight" => 15];
}
if ($role & ROLE_SYSTEMUSER) {
    $menu["email_domains"] = ["label" => "Mail-Verwaltung", "file" => "domains", "weight" => 1, "submenu" => "domains_domains"];
}
if ($role & ROLE_SYSTEMUSER && (user_has_accounts() || !user_has_vmail_domain() || user_has_dotcourier_domain())) {
    $menu["email_imap"] = ["label" => "IMAP/POP3", "file" => "imap", "weight" => 20, 'submenu' => "email_vmail"];
}
