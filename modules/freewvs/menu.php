<?php

$menu = array();

$role = $_SESSION['role'];


if ($role & ROLE_SYSTEMUSER)
{
  $menu["freewvs_freewvs"] = array("label" => "Web vulnerabilities", "file" => "freewvs.php", "weight" => 1, "submenu" => "vhosts_vhosts");
}
else
  $menu=false;