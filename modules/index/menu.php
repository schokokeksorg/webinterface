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

$role = $_SESSION['role'];

if ($role == ROLE_ANONYMOUS) {
  $menu["index_login"] = array("label" => "Login", "file" => "index", "weight" => 0);
  $menu["certlogin"] = array("label" => "Client-Zertifikat", "file" => "certinfo", "weight" => 10);
} else {
  if ($role & (ROLE_SYSTEMUSER | ROLE_SUBUSER | ROLE_VMAIL_ACCOUNT))
    $menu["index_cert"] = array("label" => "Client-Zertifikat", "file" => "cert", "weight" => 10, "submenu" => "index_index");
  if ($role & (ROLE_SYSTEMUSER | ROLE_CUSTOMER)) {
    $menu["index_chpass"] = array("label" => "Passwort ändern", "file" => "chpass", "weight" => 98);
  }

  $menu["index_logout"] = array("label" => "Logout", "file" => "logout", "weight" => 99);
  $menu["index_index"] = array("label" => "Übersicht", "file" => "index", "weight" => 0);
}


?>
