<?php
$menu = array();

$role = $_SESSION['role'];

switch ($role)
{
  case ROLE_SYSTEMUSER:
    $menu["mysql_databases"] = array("label" => "MySQL-Datenbank", "file" => "databases.php", "weight" => 20);
    
}

if (empty($menu))
  $menu = false;


?>
