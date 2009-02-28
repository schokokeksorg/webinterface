<?php

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER)
{
  $menu["webapps_freewvs"] = array("label" => "freewvs", "file" => "freewvs", "weight" => 1, "submenu" => "vhosts_vhosts");
  $menu["webapps_install"] = array("label" => "Anwendung installieren", "file" => "install", "weight" => 1, "submenu" => "vhosts_vhosts");
}
