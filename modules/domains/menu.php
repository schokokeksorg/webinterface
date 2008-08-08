<?php

$role = $_SESSION['role'];


if (($role & ROLE_CUSTOMER) || ($role & ROLE_SYSTEMUSER))
{
  $menu["domains_domains"] = array("label" => "Domains", "file" => "domains", "weight" => 1);
}

?>
