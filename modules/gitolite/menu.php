<?php

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER) {
  $menu["git_git"] = array("label" => "Git-Zugänge", "file" => "git", "weight" => 1, "submenu" => "systemuser_account");
}


?>
