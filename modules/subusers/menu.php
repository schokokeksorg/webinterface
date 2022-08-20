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

if ($role & ROLE_SYSTEMUSER) {
    $menu["subusers_subusers"] = ["label" => "Zusätzliche Admins", "file" => "subusers", "weight" => 1, "submenu" => "systemuser_account"];
    //$menu["subusers_subusers"] = array("label" => "Zusätzliche Admins", "file" => "subusers", "weight" => 1);
}
