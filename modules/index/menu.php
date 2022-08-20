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

if ($role == ROLE_ANONYMOUS) {
    $menu["index_login"] = ["label" => "Login", "file" => "index", "weight" => 0];
} else {
    if ($role & (ROLE_SYSTEMUSER | ROLE_SUBUSER | ROLE_VMAIL_ACCOUNT)) {
        $menu["index_cert"] = ["label" => "Client-Zertifikat", "file" => "cert", "weight" => 10, "submenu" => "index_index"];
    }
    if ($role & (ROLE_SYSTEMUSER | ROLE_CUSTOMER)) {
        $menu["index_chpass"] = ["label" => "Passwort ändern", "file" => "chpass", "weight" => 98];
    }

    $menu["index_logout"] = ["label" => "Logout", "file" => "logout", "weight" => 99];
    $menu["index_index"] = ["label" => "Übersicht", "file" => "index", "weight" => 0];
}
