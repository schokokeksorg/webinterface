<?php

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER)
{
  $menu["freewvs_freewvs"] = array("label" => "freewvs", "file" => "freewvs.php", "weight" => 1, "submenu" => "vhosts_vhosts");
}
