<?php

$role = $_SESSION['role'];

if ($role & ROLE_CUSTOMER)
{
  $menu["systemuser_account"] = array("label" => "Benutzeraccounts", "file" => "account", "weight" => 30);
}
elseif ($role & ROLE_SYSTEMUSER)
{
  $menu["systemuser_account"] = array("label" => "Benutzeraccount", "file" => "myaccount", "weight" => 30);
}

?>
