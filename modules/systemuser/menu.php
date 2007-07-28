<?php

$menu = array();

$role = $_SESSION['role'];

switch ($role)
{
  case ROLE_CUSTOMER:
    $menu["systemuser"] = array("label" => "Benutzeraccounts", "file" => "accounts.php", "weight" => 30);
    
}

if (empty($menu))
  $menu = false;

?>
