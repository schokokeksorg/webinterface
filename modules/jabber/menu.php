<?php

$role = $_SESSION['role'];

if ($role & ROLE_CUSTOMER)
{
  $menu["jabber_accounts"] = array("label" => "Jabber", "file" => "accounts", "weight" => 10);
    
}

?>
