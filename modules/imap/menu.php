<?php

$menu = array();

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER)
{
  $menu["imap_accounts"] = array("label" => "IMAP/POP3", "file" => "accounts.php", "weight" => 10);
}

if (empty($menu))
  $menu = false;


?>
