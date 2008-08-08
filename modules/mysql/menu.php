<?php

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER)
{
  $menu["mysql_databases"] = array("label" => "MySQL-Datenbank", "file" => "databases", "weight" => 20);
    
}
?>
