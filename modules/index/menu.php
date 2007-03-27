<?php

$role = $_SESSION['role'];

switch ($role)
{
  case ROLE_ANONYMOUS:
    $menu["index_login"] = array("label" => "Login", "file" => "index.php", "weight" => 0);
    break;
  default:
    $menu["index_logout"] = array("label" => "Logout", "file" => "logout.php", "weight" => 99);
    $menu["index_chpass"] = array("label" => "Passwort ändern", "file" => "chpass.php", "weight" => 98);
    $menu["index_index"] = array("label" => "Übersicht", "file" => "index.php", "weight" => 0);
    
}

?>
