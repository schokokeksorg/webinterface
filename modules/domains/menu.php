<?php

$menu = array();

$role = $_SESSION['role'];

switch ($role)
{
  case ROLE_ANONYMOUS:
    break;
  default:
    $menu["domains_domains"] = array("label" => "Domains", "file" => "domains.php", "weight" => 1);
    
}

if (empty($menu))
  $menu = false;

?>
