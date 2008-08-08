<?php

$role = $_SESSION['role'];

if ($role & ROLE_CUSTOMER)
{
  $menu["systemuser_accounts"] = array("label" => "Benutzeraccounts", "file" => "accounts", "weight" => 30);
}

?>
