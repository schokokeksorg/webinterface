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
    if (have_module('systemuser')) {
        $menu["su_su_customer"] = ["label" => "Benutzer wechseln", "file" => "su_customer", "weight" => -10, "submenu" => "systemuser_account"];
    } else {
        $menu["su_su_customer"] = ["label" => "Benutzer wechseln", "file" => "su_customer", "weight" => 90];
    }
}
if ($role & ROLE_SYSADMIN) {
    $menu["su_su"] = ["label" => "Su-Login", "file" => "su", "weight" => -10];
}
