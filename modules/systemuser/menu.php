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

if ($role & ROLE_CUSTOMER) {
    $menu["systemuser_account"] = ["label" => "Benutzeraccounts", "file" => "account", "weight" => 30];
} elseif ($role & ROLE_SYSTEMUSER) {
    $menu["systemuser_account"] = ["label" => "Benutzeraccount", "file" => "myaccount", "weight" => 30];
}
