<?php

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER)
{
    $menu["vhosts_vhosts"] = array("label" => "Webserver", "file" => "vhosts.php", "weight" => 1);
}

?>
