<?php

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER) {
  $menu["subusers_subusers"] = array("label" => "Zusätzliche Admins", "file" => "subusers", "weight" => 1, "submenu" => "systemuser_account");
  //$menu["subusers_subusers"] = array("label" => "Zusätzliche Admins", "file" => "subusers", "weight" => 1);
}


?>
