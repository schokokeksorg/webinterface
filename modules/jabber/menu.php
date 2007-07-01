<?php

$menu = array();

$role = $_SESSION['role'];

switch ($role)
{
  case ROLE_CUSTOMER:
    $menu["jabber_accounts"] = array("label" => "Jabber", "file" => "accounts.php", "weight" => 10);
    
}

if (empty($menu))
  $menu = false;

?>
