<?php

$menu = array();

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER)
{
    $menu["vhosts_vhosts"] = array("label" => "Webserver (VHosts)", "file" => "vhosts.php", "weight" => 1);
}

if (empty($menu))
  $menu = false;


?>
