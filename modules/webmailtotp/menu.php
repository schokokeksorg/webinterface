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

if ($role & (ROLE_MAILACCOUNT | ROLE_VMAIL_ACCOUNT)) {
    $menu["webmailtotp_account"] = ["label" => "Zwei-Faktor-Anmeldung", "file" => "account", "weight" => 5];
} elseif ($role & ROLE_SYSTEMUSER) {
    $menu["webmailtotp_overview"] = ["label" => "Zwei-Faktor-Anmeldung", "file" => "overview", "weight" => 5, "submenu" => "email_vmail" ];
}
