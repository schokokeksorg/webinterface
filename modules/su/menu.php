<?php

$menu = array();

$role = $_SESSION['role'];

if ($role & ROLE_SYSADMIN)
{
  $menu["su_su"] = array("label" => "Su-Login", "file" => "su.php", "weight" => -10);
}

if (empty($menu))
  $menu = false;

?>
