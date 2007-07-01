<?php

$menu = array();
$role = $_SESSION['role'];

switch ($role)
{
  case ROLE_ANONYMOUS:
    $menu["register_index"] = array("label" => "Kunde werden", "file" => "index.php", "weight" => 0);
    break;
    
}

if (empty($menu))
  $menu = false;

?>
