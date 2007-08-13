<?php

$menu = array();

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER)
{
  $menu["imap_accounts"] = array("label" => "IMAP/POP3", "file" => "accounts.php", "weight" => 10);
}
elseif ($role & ROLE_MAILACCOUNT)
{
  $menu["imap_chpass"] = array("label" => "Passwort ändern", "file" => "chpass.php", "weight" => 10);
}

if (empty($menu))
  $menu = false;


?>
