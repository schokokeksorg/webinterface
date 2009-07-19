<?php

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER)
{
    $menu["vhosts_vhosts"] = array("label" => "Webserver", "file" => "vhosts", "weight" => 1);
    $menu["vhosts_certs"] = array("label" => "SSL-Zertifikate", "file" => "certs", "weight" => 10, "submenu" => "vhosts_vhosts");
}

?>
