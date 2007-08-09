<?php

$menu = array();

$role = $_SESSION['role'];

if ($role & ROLE_CUSTOMER)
{
  $menu["systemuser_accounts"] = array("label" => "Benutzeraccounts", "file" => "accounts.php", "weight" => 30);
}

if (empty($menu))
  $menu = false;

?>
