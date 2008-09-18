<?php

$role = $_SESSION['role'];

if ($role & ROLE_SYSADMIN)
{
  $menu["crm_main"] = array("label" => "CRM", "file" => "main", "weight" => -9);
}

?>
